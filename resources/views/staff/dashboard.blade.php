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
            <!-- Left Column: Register Customer -->
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

                <!-- Customer Directory Quick Links -->
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

            <!-- Right Column: 2. Confirm & Update Customer Pickups + Order Intake -->
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-bold bg-white" style="color: #1B4F72;">
                        2. Confirm &amp; Update Customer Pickups ({{ count($staffPickups ?? []) }})
                    </div>
                    <div class="card-body">
                        @if (empty($staffPickups))
                            <p class="text-muted small mb-0">No pending pickup requests. Converted pickups move to Active Orders below.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Customer &amp; Items</th>
                                            <th>Pickup Details</th>
                                            <th>Current Status</th>
                                            <th>Confirm / Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($staffPickups as $pkp)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $pkp['customer_name'] ?? 'Customer' }}</div>
                                                    @if (!empty($pkp['items']) && is_array($pkp['items']))
                                                        @foreach ($pkp['items'] as $it)
                                                            <div class="small text-muted">
                                                                {{ $it['service'] }} <strong>&times;{{ $it['quantity'] }}</strong>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="small text-muted">
                                                            {{ $pkp['service'] }} &times;{{ $pkp['quantity'] ?? 1 }}
                                                        </div>
                                                    @endif
                                                    @if (!empty($pkp['estimated_total']) && $pkp['estimated_total'] > 0)
                                                        <div class="small text-muted">
                                                            Est. KES {{ number_format((float) $pkp['estimated_total'], 2) }}
                                                        </div>
                                                    @endif
                                                    @if (!empty($pkp['customer_phone']))
                                                        <div class="small text-muted">{{ $pkp['customer_phone'] }}</div>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    <div>{{ $pkp['pickup_address'] }}</div>
                                                    <div class="text-muted">Date: {{ $pkp['pickup_date'] }}</div>
                                                    @if (!empty($pkp['pickup_notes']))
                                                        <div class="text-muted fst-italic">{{ $pkp['pickup_notes'] }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $st = $pkp['status'] ?? 'pickup_requested';
                                                        $badge = match ($st) {
                                                            'collected'        => 'bg-success',
                                                            'on_the_road'      => 'bg-primary',
                                                            'arranging_pickup' => 'bg-warning text-dark',
                                                            default            => 'bg-info text-dark',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badge }} text-uppercase">
                                                        {{ str_replace('_', ' ', $st) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('staff.pickups.status', $pkp['id']) }}" class="d-flex flex-wrap gap-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="customer_id" value="{{ $pkp['customer_id'] }}">
                                                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                            <option value="pickup_requested" {{ $st === 'pickup_requested' ? 'selected' : '' }}>Pickup Requested</option>
                                                            <option value="arranging_pickup" {{ $st === 'arranging_pickup' ? 'selected' : '' }}>Arranging Pickup</option>
                                                            <option value="on_the_road" {{ $st === 'on_the_road' ? 'selected' : '' }}>On the Road</option>
                                                            <option value="collected" {{ $st === 'collected' ? 'selected' : '' }}>Collected</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Record Order from Collected Pickup (Quantity Confirmation) -->
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-white">Record Order (From Collected Pickup)</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.orders.store') }}" id="staff-order-form">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Collected Pickup</label>
                                <select name="pickup_id" id="collected-pickup-select" class="form-select" {{ empty($collectedPickups) ? 'disabled' : 'required' }}>
                                    <option value="">
                                        {{ empty($collectedPickups) ? '-- No Collected Pickups Ready --' : '-- Choose Collected Pickup --' }}
                                    </option>
                                    @foreach ($collectedPickups ?? [] as $cp)
                                        <option value="{{ $cp['id'] }}"
                                                data-customer-name="{{ $cp['customer_name'] ?? 'Customer' }}"
                                                data-service="{{ $cp['service'] }}"
                                                data-qty="{{ $cp['quantity'] ?? 1 }}">
                                            {{ $cp['customer_name'] ?? 'Customer' }} — {{ $cp['service'] }} (&times;{{ $cp['quantity'] ?? 1 }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Once recorded, the pickup moves out of Section 2 into <strong>Order Lookup &amp; Active Orders</strong>.</div>
                            </div>

                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small mb-1">Customer &amp; Requested Service</label>
                                        <div id="selected-pickup-summary" class="fw-semibold small text-dark py-1">
                                            Select a collected pickup above
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Quantity</label>
                                        <input type="number" name="quantity" id="order-quantity-input"
                                               class="form-control form-control-sm" value="1" min="1" max="500"
                                               {{ empty($collectedPickups) ? 'disabled' : 'required' }}>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Expected Ready Date</label>
                                        <input type="date" name="expected_ready_date" class="form-control form-control-sm"
                                               value="{{ date('Y-m-d', strtotime('+2 days')) }}"
                                               {{ empty($collectedPickups) ? 'disabled' : '' }}>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success" {{ empty($collectedPickups) ? 'disabled' : '' }}>
                                    Create Order &amp; Generate Tags
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Search & Recent Orders -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold">3. Order Lookup &amp; Active Orders</span>
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
                            <th>Garments &amp; Tags</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Billing &amp; Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="fw-bold">
                                    #{{ $order->order_no }}
                                    @if (!$order->bill || ($order->bill->payment_status ?? '') !== 'paid')
                                        <form method="POST" action="{{ route('staff.orders.destroy', $order->order_no) }}" class="mt-1"
                                              onsubmit="return confirm('Delete unpaid Order #{{ $order->order_no }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Delete Order</button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->customer->full_name }}<br>
                                    <small class="text-muted">{{ $order->customer->phone }}</small>
                                </td>
                                <td>
                                    @foreach ($order->garments as $g)
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1 small border-bottom pb-1">
                                            <div>
                                                <span class="badge bg-dark">{{ $g->tag_no }}</span>
                                                {{ $g->quantity }}x {{ $g->garment_type }} ({{ $g->serviceItem->service_name ?? $g->service?->service_name ?? 'Service' }})
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
                                        @if ($order->bill->payments && $order->bill->payments->count())
                                            <div class="mt-2 border-top pt-2">
                                                <div class="small fw-semibold text-muted mb-1">Payments</div>
                                                @foreach ($order->bill->payments as $payment)
                                                    <div class="d-flex align-items-center justify-content-between gap-2 small mb-1">
                                                        <span>
                                                            KES {{ number_format($payment->amount, 2) }} ({{ strtoupper($payment->payment_method) }})
                                                        </span>
                                                        <form action="{{ route('staff.payments.destroy', $payment->payment_id) }}" method="POST" class="d-inline"
                                                              onsubmit="return confirm('Delete this payment of KES {{ number_format($payment->amount, 2) }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No matching orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const collectedSelect = document.getElementById('collected-pickup-select');
        const summaryBox = document.getElementById('selected-pickup-summary');
        const qtyInput = document.getElementById('order-quantity-input');

        if (collectedSelect && summaryBox && qtyInput) {
            collectedSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) {
                    summaryBox.textContent = 'Select a collected pickup above';
                    qtyInput.value = '1';
                    return;
                }
                const custName = opt.dataset.customerName || 'Customer';
                const service = opt.dataset.service || 'Laundry';
                summaryBox.textContent = `${custName} — ${service}`;
                if (opt.dataset.qty) {
                    qtyInput.value = opt.dataset.qty;
                }
            });
        }
    </script>
</body>
</html>