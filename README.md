# Vouchering Service

## Project Overview

Vouchering Service is a Laravel-based system for issuing, redeeming, and validating vouchers. It provides a role-protected admin portal alongside a public API for voucher validation.

## Requirements

- PHP 8.3+
- MySQL or MariaDB
- Composer

Node.js and npm are also required to build or run the frontend assets.

## Environment Variables

Copy `.env.example` to `.env` and configure it for your environment. Set the standard database credentials (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`) and choose an appropriate `QUEUE_CONNECTION`.

Reverb is required for real-time admin updates. Ensure these values are configured:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Setup & Installation

Install PHP dependencies:

```bash
composer install
```

Create and configure your environment file, then generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the database and Reverb variables in `.env`, then run migrations and seed the development roles/users:

```bash
php artisan migrate --seed
```

The seeder creates these development accounts:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Viewer | `viewer@example.com` | `password` |

Install frontend dependencies:

```bash
npm install
```

## Running the Application

Run these commands in separate terminals:

```bash
php artisan serve
```

```bash
php artisan queue:work
```

```bash
php artisan reverb:start
```

```bash
npm run dev
```

## UI Testing

Sign in at `http://127.0.0.1:8000/login` with the seeded admin account:

```text
Email: admin@example.com / viewer@example.com
Password: password
```

The admin dashboard currently manages existing campaigns and generates their vouchers. Create a test campaign before opening the dashboard:

```bash
php artisan tinker
```

```php
\App\Models\Campaign::create([
    'name' => 'Black Friday Campaign',
    'msisdn_cap' => 1,
]);
```

The campaign will then appear at `/admin`, where an admin can use **Generate N Vouchers**. Viewer accounts can redeem a voucher by code from the **Redeem voucher** form, but cannot create campaigns, issue vouchers, or generate vouchers.

## Running Tests

Run the Pest test suite with:

```bash
php artisan test
```
