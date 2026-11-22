<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Aqualine Laundry Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="{{ route('owner.dashboard') }}">
            Aqualine Owner Portal ({{ Auth::user()->full_name }})
        </a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('owner.services.index') }}" class="btn btn-outline-light btn-sm">Services &amp; Rates</a>
            <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-light btn-sm">Manage Accounts</a>
            <a href="{{ route('owner.reports.print', ['from' => $from ?? now()->startOfMonth()->toDateString(), 'to' => $to ?? now()->toDateString()]) }}"
               target="_blank" class="btn btn-outline-light btn-sm">Print Report</a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Log out</button>
            </form>
        </div>
    </nav>

    <div class="container pb-5">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <!-- Date Range Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('owner.dashboard') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">From Date</label>
                        <input type="date" name="from" class="form-control form-control-sm"
                               value="{{ $from ?? now()->startOfMonth()->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">To Date</label>
                        <input type="date" name="to" class="form-control form-control-sm"
                               value="{{ $to ?? now()->toDateString() }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-dark btn-sm">Filter Report</button>
                        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Orders</div>
                        <div class="fs-4 fw-bold">{{ number_format((int) ($totalOrders ?? 0)) }}</div>
                        <div class="small text-muted">Ready / Collected: {{ number_format((int) ($completedOrders ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Revenue Collected</div>
                        <div class="fs-4 fw-bold text-success">KES {{ number_format((float) ($revenueCollected ?? 0), 2) }}</div>
                        <div class="small text-muted">
                            Cash: {{ number_format((float) ($cashTotal ?? 0), 2) }} | M-Pesa: {{ number_format((float) ($mpesaTotal ?? 0), 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Outstanding Balances</div>
                        <div class="fs-4 fw-bold text-danger">KES {{ number_format((float) ($outstandingBalance ?? 0), 2) }}</div>
                        <div class="small text-muted">Unpaid / Partial bills</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Registered Customers</div>
                        <div class="fs-4 fw-bold">{{ isset($customers) ? $customers->count() : 0 }}</div>
                        <div class="small text-muted">Active customer base</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payments Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Recent Payments</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paid At</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>M-Pesa Ref</th>
                            <th>Received By</th>
                            <th class="text-end">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($recentPayments ?? collect()) as $pay)
                            @php
                                $order = $pay->bill?->order;
                                $customer = $order?->customer;
                            @endphp
                            <tr>
                                <td class="small">{{ $pay->paid_at }}</td>
                                <td>#{{ $order?->order_no ?? $pay->bill?->order_no ?? 'N/A' }}</td>
                                <td>{{ $customer?->full_name ?? 'Customer' }}</td>
                                <td>
                                    <span class="badge {{ ($pay->payment_method ?? '') === 'mpesa' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ strtoupper((string) ($pay->payment_method ?? 'cash')) }}
                                    </span>
                                </td>
                                <td class="small">{{ $pay->mpesa_ref ?? '-' }}</td>
                                <td class="small">{{ $pay->receiver?->full_name ?? 'Staff' }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) ($pay->amount ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No payments recorded in this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Unpaid / Pending Bills -->
        @if (!empty($unpaidBills) && $unpaidBills->count())
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Unpaid &amp; Partially Paid Bills ({{ $unpaidBills->count() }})</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bill #</th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th class="text-end">Balance (KES)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unpaidBills as $ub)
                                @php
                                    $ubOrder = $ub->order;
                                    $ubCustomer = $ubOrder?->customer;
                                    $ubBalance = max(0, (float) ($ub->total_amount ?? 0) - (float) ($ub->amount_paid ?? 0));
                                @endphp
                                <tr>
                                    <td>#{{ $ub->bill_id }}</td>
                                    <td>#{{ $ubOrder?->order_no ?? $ub->order_no ?? 'N/A' }}</td>
                                    <td>{{ $ubCustomer?->full_name ?? 'Customer' }}</td>
                                    <td class="text-end fw-bold text-danger">{{ number_format($ubBalance, 2) }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark text-uppercase">
                                            {{ $ub->payment_status ?? 'unpaid' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</body>
</html>