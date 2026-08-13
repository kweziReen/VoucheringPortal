# Design Notes

## Schema

`campaigns` holds the campaign name and its per-MSISDN cap. `vouchers` belongs to a campaign and has a unique, externally usable `code`; `issued_at` and `redeemed_at` represent the voucher lifecycle, with `issued_at` indexed for allocation queries. `redemptions` is kept as a separate record rather than relying only on `vouchers.redeemed_at`, preserving an audit row for every successful redemption. Foreign keys prevent orphaned vouchers/redemptions, and the voucher code, MSISDN hash, and issuance indexes support the API's lookup paths.

## Concurrency and voucher issuance

Voucher issuance must not allocate more vouchers than are available or allow an MSISDN to exceed a campaign's cap when requests arrive at the same time.

`POST /api/v1/vouchers/issue` normalizes the supplied MSISDN (digits only) and derives a deterministic `SHA-256` hash. Issuance then runs in a database transaction:

1. The campaign row is locked with `FOR UPDATE`. This serializes issuance attempts for that campaign, so the cap check sees allocations made by the preceding request.
2. The number of issued vouchers for the campaign and MSISDN hash is checked against `campaign.msisdn_cap` while that lock is held.
3. An available voucher is claimed with a conditional SQL `UPDATE` requiring `issued_at IS NULL` and affecting at most one row. The code does not load an unissued voucher model and call `save()`.

The campaign lock was chosen over a plain SELECT-then-save flow because it makes the count-and-claim sequence serial for a campaign. The conditional update is still required to make the claim itself safe. A count query without the lock can let concurrent transactions both observe spare cap; a model `save()` after selecting an available voucher can let both callers claim it. If the cap is reached, the API returns `422`; if no unissued voucher remains, it returns `409`.

Voucher redemption uses the same pattern at row level: it updates only vouchers whose `issued_at` is present and whose `redeemed_at` is `NULL`. A zero-row update is reported as `409 Conflict` when the voucher exists but is unissued or already redeemed. The successful transaction creates exactly one `Redemption` record.

## SMS failure handling

SMS delivery is isolated in `SendVoucherSmsJob`. The job reads the decrypted MSISDN through Laravel's encrypted model cast only at the point it sends the message.

The current `FakeSmsClient` is an HTTP client configured in `config/sms.php`:

- Connection timeout: 2 seconds by default.
- Request timeout: 5 seconds by default.
- Job timeout: 15 seconds, with `failOnTimeout` enabled.
- Attempts: 4 total, with retry delays of 5, 30, and 120 seconds.

Timeout-like connection failures and HTTP 5xx responses are deliberately not swallowed. They escape the job so Laravel marks the attempt as failed and retries it using the backoff schedule; a failed provider therefore does not crash the worker process or block the queue indefinitely. After a successful send, the job broadcasts `VoucherIssued` with `sms_status: sent`. Laravel calls the job's `failed()` hook only after its final failed attempt, which broadcasts `sms_status: failed`.

If the provider hangs for 60 seconds, the HTTP request is cut off at 5 seconds (or 2 seconds while connecting); the worker also kills the job at 15 seconds if a lower-level client call cannot return. The attempt is then retried rather than holding a worker for a minute. If Reverb is down, voucher issuance and SMS delivery are already committed and are not rolled back. The queued broadcast attempt can fail and be retried by the queue worker; live admin updates may be delayed or absent, but the API and database state remain correct.

## PII decisions

The MSISDN is not placed in API responses, admin tables, logs, or WebSocket payloads.

- `msisdn_encrypted` uses Laravel's `encrypted` cast. The stored database value is ciphertext protected by `APP_KEY`; application code receives plaintext only when reading the model attribute.
- `msisdn_hash` is nullable and indexed. It allows a fast equality lookup for the per-campaign cap without decrypting every stored number.
- The current hash is a normalized, deterministic `SHA-256` digest. This makes it linkable within the database and could be vulnerable to guessing for a small telephone-number space. In a production deployment it should be replaced with a keyed HMAC (using a separately managed secret) if the database may be exposed.
- The `VoucherIssued` Reverb event has an explicit `broadcastWith()` payload containing only `voucher_code`, `campaign_id`, `issued_at`, and `sms_status`. It does not retain or serialize the voucher model, so neither the encrypted value nor its hash is sent to subscribers.
- The `admin.vouchers` channel is private, its authorization endpoint requires Sanctum authentication, and its channel callback requires the Spatie `admin` role. Viewers cannot subscribe.

The fake SMS endpoint and Reverb credentials in `.env.example` are development defaults only. Production secrets, `APP_KEY`, database credentials, and provider credentials must be managed outside version control.

## Assistance

This implementation was produced with LLM assistance.
