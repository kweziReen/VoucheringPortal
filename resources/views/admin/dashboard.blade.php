@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Campaigns</h1>
        <span class="badge text-bg-secondary">{{ auth()->user()->getRoleNames()->implode(', ') }}</span>
    </div>
    <div class="card mb-3"><div class="card-body">
        <h2 class="h5">Redeem voucher</h2>
        <form class="row g-2" method="POST" action="{{ route('admin.vouchers.redeem') }}">
            @csrf
            <div class="col-md-9"><label class="visually-hidden" for="voucher-code">Voucher code</label><input class="form-control" id="voucher-code" name="code" value="{{ old('code') }}" placeholder="Voucher code" required></div>
            <div class="col-md-3"><button class="btn btn-warning w-100">Redeem voucher</button></div>
        </form>
    </div></div>
    @role('admin')
        <div class="card mb-3"><div class="card-body">
            <h2 class="h5">Create campaign</h2>
            <form class="row g-2" method="POST" action="{{ route('admin.campaigns.store') }}">
                @csrf
                <div class="col-md-7"><label class="visually-hidden" for="campaign-name">Name</label><input class="form-control @error('name') is-invalid @enderror" id="campaign-name" name="name" value="{{ old('name') }}" placeholder="Campaign name" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-3"><label class="visually-hidden" for="msisdn-cap">MSISDN cap</label><input class="form-control @error('msisdn_cap') is-invalid @enderror" id="msisdn-cap" name="msisdn_cap" type="number" value="{{ old('msisdn_cap', 1) }}" min="1" placeholder="MSISDN cap" required>@error('msisdn_cap')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Create campaign</button></div>
            </form>
        </div></div>
    @endrole
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.dashboard') }}">
        <div class="col-sm-5"><input class="form-control" name="campaign_search" value="{{ $campaignSearch }}" placeholder="Search campaigns"></div>
        <div class="col-auto"><button class="btn btn-outline-primary">Search</button></div>
    </form>
    <div class="card mb-5"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Name</th><th>MSISDN cap</th><th class="text-end">Action</th></tr></thead><tbody>
        @forelse ($campaigns as $campaign)
            <tr><td>{{ $campaign->name }}</td><td>{{ $campaign->msisdn_cap }}</td><td class="text-end">
                @role('admin')
                    <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                        <form class="d-inline-flex gap-2" method="POST" action="{{ route('admin.campaigns.vouchers.issue', $campaign) }}">
                            @csrf
                            <input class="form-control form-control-sm" type="text" name="msisdn" placeholder="MSISDN" aria-label="MSISDN" required>
                            <button class="btn btn-sm btn-success">Issue voucher</button>
                        </form>
                        <form class="d-inline-flex gap-2" method="POST" action="{{ route('admin.campaigns.vouchers.generate', $campaign) }}">
                            @csrf
                            <input class="form-control form-control-sm" type="number" name="quantity" value="1000" min="1" max="100000" aria-label="Number of vouchers">
                            <button class="btn btn-sm btn-primary">Generate N Vouchers</button>
                        </form>
                    </div>
                @else<span class="text-muted small">Read only</span>@endrole
            </td></tr>
        @empty<tr><td colspan="3" class="text-center text-muted py-4">No campaigns found.</td></tr>@endforelse
        </tbody>
    </table></div><div class="card-body">{{ $campaigns->links() }}</div></div>

    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h3 mb-0">Vouchers</h2>
        <form method="GET" action="{{ route('admin.dashboard') }}">
            <input type="hidden" name="campaign_search" value="{{ $campaignSearch }}">
            <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option><option value="available" @selected($status === 'available')>Available</option>
                <option value="issued" @selected($status === 'issued')>Issued</option><option value="redeemed" @selected($status === 'redeemed')>Redeemed</option>
            </select>
        </form>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0" data-voucher-table>
        <thead><tr><th>Code</th><th>Campaign</th><th>Status</th><th>Issued</th><th>Redeemed</th></tr></thead><tbody>
        @forelse ($vouchers as $voucher)
            <tr><td>{{ $voucher->code }}</td><td>{{ $voucher->campaign->name }}</td><td>
                @if ($voucher->redeemed_at)<span class="badge text-bg-success">Redeemed</span>
                @elseif ($voucher->issued_at)<span class="badge text-bg-primary">Issued</span>
                @else<span class="badge text-bg-secondary">Available</span>@endif
            </td><td>{{ $voucher->issued_at?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $voucher->redeemed_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">No vouchers found.</td></tr>@endforelse
        </tbody>
    </table></div><div class="card-body">{{ $vouchers->links() }}</div></div>
@endsection
