<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard & Reports - Aqualine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 no-print">
        <span class="navbar-brand fw-bold">Aqualine Owner Portal</span>
        <div class="d-flex gap-2">
            <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-light btn-sm">Manage Staff</a>
            <a href="{{ route('owner.services.index') }}" class="btn btn-outline-light btn-sm">Services &amp; Pricing</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Log out</button>
            </form>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="mb-0">Business Overview &amp; Revenue Report</h4>
                <small class="text-muted">Period: {{ $from }} to {{ $to }}</small>
            </div>
            <form method="GET" action="{{ route('owner.dashboard') }}" class="d-flex align-items-center gap-2 no-print">
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                <span>to</span>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                <button type="submit" class="btn btn-dark btn-sm">Filter</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print Report</button>
            </form>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <div class="text-muted small">Total Revenue Collected</div>
                        <h4 class="fw-bold mb-0">KES {{ number_format($totalRevenue, 2) }}</h4>
                        <small class="text-muted">Cash: {{ number_format($cashRevenue, 0) }} | M-Pesa: {{ number_format($mpesaRevenue, 0) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="text-muted small">Orders Received</div>
                        <h4 class="fw-bold mb-0">{{ $ordersCount }}</h4>
                        <small class="text-muted">Ready / Collected: {{ $completedOrders }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="text-muted small">Outstanding Unpaid Balances</div>
                        <h4 class="fw-bold mb-0">KES {{ number_format($outstandingBalance, 2) }}</h4>
                        <small class="text-muted">Across all open bills</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 no-print">
                <div class="card shadow-sm bg-dark text-white h-100">
                    <div class="card-body d-flex flex-column justify-content-center gap-2">
                        <a href="{{ route('owner.services.index') }}" class="btn btn-light btn-sm fw-bold">Update Service Prices</a>
                        <a href="{{ route('owner.staff.index') }}" class="btn btn-outline-light btn-sm">Manage Staff Accounts</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Transactions Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Payment Collections Log ({{ $from }} to {{ $to }})</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>M-Pesa Ref</th>
                            <th>Received By</th>
                            <th class="text-end">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPayments as $pay)
                            <tr>
                                <td class="small">{{ $pay->paid_at }}</td>
                                <td>#{{ $pay->bill->order->order_no }}</td>
                                <td>{{ $pay->bill->order->customer->full_name }}</td>
                                <td><span class="badge {{ $pay->payment_method === 'mpesa' ? 'bg-success' : 'bg-secondary' }}">{{ strtoupper($pay->payment_method) }}</span></td>
                                <td class="small">{{ $pay->mpesa_ref ?? '-' }}</td>
                                <td class="small">{{ $pay->receiver->full_name ?? 'Staff' }}</td>
                                <td class="text-end fw-bold">{{ number_format($pay->amount, 2) }}</td>
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
    </div>
</body>
</html>