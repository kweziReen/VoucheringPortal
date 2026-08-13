<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateVouchersJob;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Campaign;
use App\Models\Voucher;
use App\Services\VoucherIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $campaignSearch = (string) $request->query('campaign_search', '');
        $status = (string) $request->query('status', '');

        $campaigns = Campaign::query()
            ->when($campaignSearch !== '', fn ($query) => $query->where('name', 'like', "%{$campaignSearch}%"))
            ->orderBy('name')
            ->paginate(15, ['*'], 'campaign_page')
            ->withQueryString();

        $vouchers = Voucher::query()
            ->with('campaign:id,name')
            ->when($status === 'available', fn ($query) => $query->whereNull('issued_at'))
            ->when($status === 'issued', fn ($query) => $query->whereNotNull('issued_at')->whereNull('redeemed_at'))
            ->when($status === 'redeemed', fn ($query) => $query->whereNotNull('redeemed_at'))
            ->latest('id')
            ->paginate(20, ['*'], 'voucher_page')
            ->withQueryString();

        return view('admin.dashboard', compact('campaigns', 'campaignSearch', 'status', 'vouchers'));
    }

    public function generate(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000']]);
        GenerateVouchersJob::dispatch($campaign->id, $validated['quantity']);

        return back()->with('status', "Generation of {$validated['quantity']} vouchers has been queued.");
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'msisdn_cap' => ['required', 'integer', 'min:1'],
        ]);

        Campaign::query()->create($validated);

        return back()->with('status', 'Campaign created successfully.');
    }

    public function issue(Request $request, Campaign $campaign, VoucherIssuer $voucherIssuer): RedirectResponse
    {
        $validated = $request->validate(['msisdn' => ['required', 'string', 'min:3', 'max:32']]);
        $result = $voucherIssuer->issue($campaign->id, $validated['msisdn']);

        if ($result['status'] !== 'issued') {
            $message = match ($result['status']) {
                'cap_reached' => 'This MSISDN has reached the campaign cap.',
                'unavailable' => 'No vouchers are available for this campaign.',
                default => 'The MSISDN is invalid.',
            };

            return back()->withErrors(['msisdn' => $message]);
        }

        SendVoucherSmsJob::dispatch($result['voucher']);

        return back()->with('status', "Voucher {$result['voucher']->code} was issued and queued for SMS delivery.");
    }
}
