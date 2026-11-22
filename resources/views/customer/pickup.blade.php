<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Pickup - Aqualine Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; color: #1F2937; }
        .aqualine-navbar { background-color: #1B4F72; border-bottom: 3px solid #0F766E; }
        .btn-aqualine { background-color: #0F766E; border-color: #0F766E; color: #fff; font-weight: 600; }
        .btn-aqualine:hover { background-color: #0B5E58; border-color: #0B5E58; color: #fff; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark aqualine-navbar shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('customer.dashboard') }}">
                Aqualine Customer Portal ({{ Auth::user()->full_name }})
            </a>
            <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-light btn-sm">&larr; Back to Dashboard</a>
        </div>
    </nav>

    <div class="container pb-5">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="qty-limit-alert" class="alert alert-warning d-none" role="alert"></div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3" style="color: #1B4F72;">
                        Request Laundry Pickup
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('customer.pickup.store') }}" id="pickup-request-form">
                            @csrf

                            @php
                                $initialItems = old('items');
                                if (!is_array($initialItems) || empty($initialItems)) {
                                    $initialItems = [
                                        [
                                            'service'  => $selectedService ?? ($services->first()->service_name ?? 'General Laundry'),
                                            'quantity' => 1,
                                        ],
                                    ];
                                }
                            @endphp

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold">Services &amp; Quantities (Max 20 each)</label>
                                    <button type="button" id="add-pickup-item-btn" class="btn btn-outline-dark btn-sm">
                                        + Add Another Service
                                    </button>
                                </div>

                                <div id="pickup-items-container">
                                    @foreach ($initialItems as $idx => $row)
                                        @php
                                            $rowService = $row['service'] ?? '';
                                            $rowQty = $row['quantity'] ?? 1;
                                        @endphp
                                        <div class="border rounded p-2 mb-2 bg-light pickup-item-row">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-sm-7">
                                                    <label class="form-label small mb-1">Service / Laundry Type</label>
                                                    <select name="items[{{ $idx }}][service]" class="form-select form-select-sm" required>
                                                        @foreach ($services as $srv)
                                                            <option value="{{ $srv->service_name }}" {{ $rowService === $srv->service_name ? 'selected' : '' }}>
                                                                {{ $srv->service_name }} (KES {{ number_format((float) $srv->unit_price, 2) }} / {{ $srv->unit }})
                                                            </option>
                                                        @endforeach
                                                        @if ($rowService !== '' && !$services->contains('service_name', $rowService))
                                                            <option value="{{ $rowService }}" selected>{{ $rowService }}</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label class="form-label small mb-1">Qty (1–20)</label>
                                                    <input type="number" name="items[{{ $idx }}][quantity]"
                                                           class="form-control form-control-sm pickup-qty-input"
                                                           value="{{ $rowQty }}" min="1" max="20" step="1" required>
                                                </div>
                                                <div class="col-sm-2 d-grid">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-pickup-item-btn" title="Remove item">&times;</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">You can add multiple services (e.g., Duvet + Suit) and enter up to 20 per item.</div>
                            </div>

                            @if ($orders->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label">Related Order (Optional)</label>
                                    <select name="order_no" class="form-select">
                                        <option value="">-- New / General Pickup --</option>
                                        @foreach ($orders as $ord)
                                            <option value="{{ $ord->order_no }}">Order #{{ $ord->order_no }} ({{ $ord->status }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Pickup Address</label>
                                <input type="text" name="pickup_address" class="form-control"
                                       value="{{ old('pickup_address') }}" placeholder="e.g. Madaraka Estate, Block B, House 12" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Preferred Pickup Date</label>
                                <input type="date" name="pickup_date" class="form-control"
                                       min="{{ now()->toDateString() }}"
                                       value="{{ old('pickup_date', now()->toDateString()) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pickup Notes (Optional)</label>
                                <textarea name="pickup_notes" rows="3" class="form-control"
                                          placeholder="Gate instructions, bag count, preferred time...">{{ old('pickup_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-aqualine">Submit Pickup Request</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- 1. Pickup Requests -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold py-3" style="color: #1B4F72;">
                        Pickup Requests ({{ count($pickupRequests ?? []) }})
                    </div>
                    <div class="card-body">
                        @if (empty($pickupRequests))
                            <p class="text-muted small mb-0">No pickup requests submitted yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Services &amp; Qty</th>
                                            <th>Address &amp; Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pickupRequests as $req)
                                            @php
                                                $pSt = $req['status'] ?? 'pickup_requested';
                                                $pBadge = match ($pSt) {
                                                    'collected'        => 'bg-success',
                                                    'on_the_road'      => 'bg-primary',
                                                    'arranging_pickup' => 'bg-warning text-dark',
                                                    default            => 'bg-info text-dark',
                                                };
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">
                                                    @if (!empty($req['items']) && is_array($req['items']))
                                                        @foreach ($req['items'] as $it)
                                                            <div>
                                                                {{ $it['service'] }}
                                                                <span class="badge bg-secondary ms-1">&times;{{ $it['quantity'] }}</span>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        {{ $req['service'] }}
                                                        <span class="badge bg-secondary ms-1">&times;{{ $req['quantity'] ?? 1 }}</span>
                                                    @endif
                                                    @if (!empty($req['estimated_total']) && $req['estimated_total'] > 0)
                                                        <div class="text-muted small fw-normal mt-1">
                                                            Est. Total: KES {{ number_format((float) $req['estimated_total'], 2) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    {{ $req['pickup_address'] }}<br>
                                                    <span class="text-muted">{{ $req['pickup_date'] }}</span>
                                                </td>
                                                <td><span class="badge {{ $pBadge }} text-uppercase">{{ str_replace('_', ' ', $pSt) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 2. Delivery Requests -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-bold py-3" style="color: #1B4F72;">
                        Delivery Requests ({{ ($deliveryRequests ?? collect())->count() }})
                    </div>
                    <div class="card-body">
                        @if (($deliveryRequests ?? collect())->isEmpty())
                            <p class="text-muted small mb-0">No delivery requests submitted yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Address &amp; Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deliveryRequests as $dOrd)
                                            <tr>
                                                <td>#{{ $dOrd->order_no }}</td>
                                                <td class="small">
                                                    {{ $dOrd->delivery_address ?? '-' }}<br>
                                                    <span class="text-muted">{{ $dOrd->delivery_date ?? '-' }}</span>
                                                </td>
                                                <td><span class="badge bg-warning text-dark">{{ $dOrd->delivery_status }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 3. Orders & In Progress -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3" style="color: #1B4F72;">
                        Orders &amp; In Progress ({{ $orders->count() }})
                    </div>
                    <div class="card-body">
                        @if ($orders->isEmpty())
                            <p class="text-muted small mb-0">No active laundry orders in progress yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Dropped Off</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $ord)
                                            <tr>
                                                <td>#{{ $ord->order_no }}</td>
                                                <td class="small">{{ \Carbon\Carbon::parse($ord->date_received)->format('d M Y') }}</td>
                                                <td><span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', $ord->status) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('pickup-items-container');
            const addBtn = document.getElementById('add-pickup-item-btn');
            const form = document.getElementById('pickup-request-form');
            const alertBox = document.getElementById('qty-limit-alert');

            let itemIndex = container ? container.querySelectorAll('.pickup-item-row').length : 1;

            function showQtyError(message) {
                if (!alertBox) return;
                alertBox.textContent = message;
                alertBox.classList.remove('d-none');
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function hideQtyError() {
                if (!alertBox) return;
                alertBox.textContent = '';
                alertBox.classList.add('d-none');
            }

            function bindRowEvents(row) {
                const qtyInput = row.querySelector('.pickup-qty-input');
                const removeBtn = row.querySelector('.remove-pickup-item-btn');

                if (qtyInput) {
                    qtyInput.addEventListener('input', function () {
                        const val = parseInt(this.value, 10);
                        if (!Number.isNaN(val) && val > 20) {
                            showQtyError('Maximum allowed quantity per item is 20 (must be 20 or less).');
                        } else {
                            hideQtyError();
                        }
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const rows = container.querySelectorAll('.pickup-item-row');
                        if (rows.length > 1) {
                            row.remove();
                            hideQtyError();
                        }
                    });
                }
            }

            if (container) {
                container.querySelectorAll('.pickup-item-row').forEach(bindRowEvents);
            }

            if (addBtn && container) {
                addBtn.addEventListener('click', function () {
                    const firstRow = container.querySelector('.pickup-item-row');
                    if (!firstRow) return;

                    const clone = firstRow.cloneNode(true);
                    clone.querySelectorAll('select, input').forEach(el => {
                        el.name = el.name.replace(/items\[\d+\]/, `items[${itemIndex}]`);
                        if (el.type === 'number') {
                            el.value = '1';
                        }
                    });

                    container.appendChild(clone);
                    bindRowEvents(clone);
                    itemIndex++;
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    const qtyInputs = form.querySelectorAll('.pickup-qty-input');
                    for (const input of qtyInputs) {
                        const val = parseInt(input.value, 10);
                        if (Number.isNaN(val) || val < 1) {
                            e.preventDefault();
                            showQtyError('Each item quantity must be at least 1.');
                            input.focus();
                            return;
                        }
                        if (val > 20) {
                            e.preventDefault();
                            showQtyError(`You entered ${val}. Each item quantity must be 20 or less (maximum is 20).`);
                            input.focus();
                            return;
                        }
                    }
                    hideQtyError();
                });
            }
        })();
    </script>
</body>
</html>