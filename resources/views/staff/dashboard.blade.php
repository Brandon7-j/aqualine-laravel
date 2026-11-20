<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Counter - Aqualine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary px-4">
        <span class="navbar-brand fw-bold">Aqualine Staff Counter ({{ Auth::user()->full_name }})</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm">Log out</button>
        </form>
    </nav>

    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-4">
            <!-- Left Column: Register Customer (FR10) -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-bold bg-white">1. Register New Customer</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.customers.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" placeholder="e.g. Brian Kamau" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Phone Number (Login ID)</label>
                                <input type="text" name="phone" class="form-control" placeholder="e.g. 0722111222" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email (Optional)</label>
                                <input type="email" name="email" class="form-control" placeholder="brian@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Min 6 chars" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Save Customer</button>
                        </form>
                    </div>
                </div>

                <!-- Customer Directory Quick Links (FR12) -->
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-white">Registered Customers</div>
                    <div class="list-group list-group-flush" style="max-height: 240px; overflow-y: auto;">
                        @forelse ($customers as $cust)
                            <a href="{{ route('staff.customers.show', $cust->user_id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $cust->full_name }}</strong><br>
                                    <small class="text-muted">{{ $cust->phone }}</small>
                                </div>
                                <span class="badge bg-secondary">View / Edit</span>
                            </a>
                        @empty
                            <div class="p-3 text-muted small">No customers registered yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column: Create Order & Garments (FR13 & FR14) -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-white">2. Create Order & Record Garments</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.orders.store') }}">
                            @csrf
                            <div class="row g-2 mb-3">
                                <div class="col-md-7">
                                    <label class="form-label">Select Customer</label>
                                    <select name="customer_id" class="form-select" required>
                                        <option value="">-- Choose Customer --</option>
                                        @foreach ($customers as $cust)
                                            <option value="{{ $cust->user_id }}">{{ $cust->full_name }} ({{ $cust->phone }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Expected Ready Date</label>
                                    <input type="date" name="expected_ready_date" class="form-control" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                                </div>
                            </div>

                            <h6 class="fw-bold mt-3">Garment Details</h6>
                            <div id="garments-container">
                                <div class="border rounded p-3 mb-2 bg-light garment-row">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Service</label>
                                            <select name="garments[0][service_id]" class="form-select form-select-sm" required>
                                                @foreach ($services as $srv)
                                                    <option value="{{ $srv->service_id }}">{{ $srv->service_name }} (KES {{ number_format($srv->unit_price, 2) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Garment Type</label>
                                            <input type="text" name="garments[0][garment_type]" class="form-control form-control-sm" placeholder="e.g. Official Shirt" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Qty</label>
                                            <input type="number" name="garments[0][quantity]" class="form-control form-control-sm" value="1" min="1" required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" name="garments[0][colour]" class="form-control form-control-sm" placeholder="Colour (e.g. White)">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" name="garments[0][condition_note]" class="form-control form-control-sm" placeholder="Condition note (e.g. Missing button)">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" id="add-garment-btn" class="btn btn-outline-secondary btn-sm">+ Add Another Garment</button>
                                <button type="submit" class="btn btn-success">Create Order & Generate Tags</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Search & Recent Orders (FR15) -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold">3. Order Lookup & Active Orders </span>
                <form method="GET" action="{{ route('staff.dashboard') }}" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Order # or Customer Phone...">
                    <button type="submit" class="btn btn-dark btn-sm">Search</button>
                    @if ($search !== '')
                        <a href="{{ route('staff.dashboard') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Garments & Tags</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Billing & Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="fw-bold">#{{ $order->order_no }}</td>
                                <td>
                                    {{ $order->customer->full_name }}<br>
                                    <small class="text-muted">{{ $order->customer->phone }}</small>
                                </td>
                                <td>
                              <td>
                                    @foreach ($order->garments as $g)
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1 small border-bottom pb-1">
                                            <div>
                                                <span class="badge bg-dark">{{ $g->tag_no }}</span>
                                                {{ $g->quantity }}x {{ $g->garment_type }} ({{ $g->service->service_name }})
                                            </div>
                                           <form method="POST" action="{{ route('staff.garments.status', $g->garment_id) }}" class="d-flex gap-1">
    @csrf
    @method('PATCH')
    <select name="status" class="form-select form-select-sm" style="width: 130px;">
        @foreach (['received', 'washing', 'ironing', 'in_process', 'ready', 'collected'] as $st)
            <option value="{{ $st }}" {{ $g->status === $st ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $st)) }}
            </option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
</form>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="small">{{ $order->date_received }}</td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span></td>
                                <td>
    @if (!$order->bill)
        <form method="POST" action="{{ route('staff.bills.generate', $order->order_no) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success">Generate Bill</button>
        </form>
    @else
        @php $balance = $order->bill->total_amount - $order->bill->amount_paid; @endphp
        <div class="small mb-1">
            <strong>Total:</strong> KES {{ number_format($order->bill->total_amount, 2) }}<br>
            <strong>Paid:</strong> KES {{ number_format($order->bill->amount_paid, 2) }}
            <span class="badge {{ $order->bill->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                {{ strtoupper($order->bill->payment_status) }}
            </span>
        </div>

        @if ($order->bill->payment_status !== 'paid')
            <form method="POST" action="{{ route('staff.payments.store', $order->bill->bill_id) }}" class="border rounded p-2 bg-light">
                @csrf
                <div class="input-group input-group-sm mb-1">
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ $balance }}" max="{{ $balance }}" required>
                    <select name="payment_method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                    </select>
                </div>
                <input type="text" name="mpesa_ref" class="form-control form-control-sm mb-1" placeholder="M-Pesa Code (if M-Pesa)">
                <button type="submit" class="btn btn-sm btn-success w-100">Record Payment</button>
            </form>
        @elseif ($order->bill->receipt)
            <a href="{{ route('receipts.show', $order->bill->receipt->receipt_id) }}" target="_blank" class="btn btn-sm btn-dark">
                View Receipt ({{ $order->bill->receipt->receipt_no }})
            </a>
        @endif
    @endif
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No matching orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let garmentIndex = 1;
        document.getElementById('add-garment-btn').addEventListener('click', function () {
            const container = document.getElementById('garments-container');
            const firstRow = container.querySelector('.garment-row');
            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('input, select').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, `[${garmentIndex}]`);
                if (el.tagName === 'INPUT') {
                    el.value = el.type === 'number' ? '1' : '';
                }
            });

            container.appendChild(newRow);
            garmentIndex++;
        });
    </script>
</body>
</html>