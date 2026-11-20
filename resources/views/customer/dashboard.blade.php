<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Hub - Aqualine Laundry Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #F8FAFC;
            --bg-surface: #FFFFFF;
            --brand-primary: #1B4F72;
            --accent-cta: #0F766E;
            --accent-cta-hover: #0B5E58;
            --text-dark: #1F2937;
            --border-subtle: #E2E8F0;
            --hover-tint: #EFF7F6;
        }
        body {
            background-color: var(--bg-canvas);
            color: var(--text-dark);
        }
        .aqualine-navbar {
            background-color: var(--brand-primary);
            border-bottom: 3px solid var(--accent-cta);
        }
        .btn-aqualine {
            background-color: var(--accent-cta);
            border-color: var(--accent-cta);
            color: #ffffff;
            font-weight: 600;
        }
        .btn-aqualine:hover {
            background-color: var(--accent-cta-hover);
            border-color: var(--accent-cta-hover);
            color: #ffffff;
        }
        .hub-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hub-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(27, 79, 114, 0.08);
        }
        .table-aqualine thead th {
            background-color: var(--brand-primary);
            color: #ffffff;
        }
        .table-aqualine tbody tr:hover {
            background-color: var(--hover-tint);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark aqualine-navbar shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('customer.dashboard') }}">
                Aqualine Customer Portal ({{ Auth::user()->full_name }})
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="customerNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" href="#my-orders">Check Orders &amp; Billing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customer.delivery.form') }}">Request Delivery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#all-services">All Services &amp; Rates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/laundry-system/index.php">&larr; Aqualine Home</a>
                    </li>
                </ul>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Log out</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Quick Action Hub Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card hub-card h-100 p-3">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-primary mb-2">REAL-TIME TRACKING</span>
                            <h5 class="fw-bold" style="color: #1B4F72;">Check Order &amp; Pay Bill</h5>
                            <p class="text-muted small">Track garment progress from drop-off to ready, settle via M-Pesa, and open receipts.</p>
                        </div>
                        <a href="#my-orders" class="btn btn-aqualine btn-sm w-100 mt-2">View My Orders ({{ $orders->count() }})</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card hub-card h-100 p-3">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-info text-dark mb-2">MADARAKA &amp; ENVIRONS</span>
                            <h5 class="fw-bold" style="color: #1B4F72;">Request Pickup / Delivery</h5>
                            <p class="text-muted small">Schedule doorstep laundry collection or delivery through our Madaraka dispatch desk.</p>
                        </div>
                        <a href="{{ route('customer.delivery.form') }}" class="btn btn-outline-dark btn-sm w-100 mt-2">Request a Delivery &rarr;</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card hub-card h-100 p-3">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-secondary mb-2">LIVE PRICE LIST</span>
                            <h5 class="fw-bold" style="color: #1B4F72;">All Laundry Services</h5>
                            <p class="text-muted small">Browse washing, drying, ironing, folding, and dry-cleaning rates in KES.</p>
                        </div>
                        <a href="#all-services" class="btn btn-outline-dark btn-sm w-100 mt-2">View Live Rates &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Check Orders, Garment Status & Billing -->
        <section id="my-orders" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0" style="color: #1B4F72;">My Laundry Orders &amp; Garment Status</h4>
                <a href="{{ route('customer.delivery.form') }}" class="btn btn-aqualine btn-sm">+ Request Delivery</a>
            </div>

            @if ($orders->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="alert alert-info mb-3">
                            You do not have any laundry orders yet.
                        </div>
                        <p class="text-muted mb-3">
                            Ready to send your laundry? Request a doorstep delivery or check our live service rates below.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('customer.delivery.form') }}" class="btn btn-aqualine">Request a Delivery</a>
                            <a href="#all-services" class="btn btn-outline-dark">View All Services &amp; Rates</a>
                        </div>
                    </div>
                </div>
            @else
                @foreach ($orders as $order)
                    @php
                        $bill = $order->bill;
                        $billId = $bill->bill_id ?? $bill->bill_no ?? null;
                        $billTotal = $bill ? (float) ($bill->total_amount ?? $bill->amount_due ?? 0) : 0;
                        $billPaid = $bill ? (float) ($bill->amount_paid ?? 0) : 0;
                        $billBalance = max(0, $billTotal - $billPaid);
                        $paymentStatus = $bill ? strtolower((string) ($bill->payment_status ?? 'unpaid')) : 'unbilled';
                    @endphp

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <span class="fw-bold fs-5" style="color: #1B4F72;">Order #{{ $order->order_no }}</span>
                                <span class="text-muted small ms-2">
                                    Drop-off: {{ \Carbon\Carbon::parse($order->date_received)->format('d M Y, H:i') }}
                                    @if ($order->expected_ready_date)
                                        &bull; Expected Ready: {{ \Carbon\Carbon::parse($order->expected_ready_date)->format('d M Y') }}
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($order->delivery_status)
                                    <span class="badge bg-info text-dark text-uppercase px-2 py-2">
                                        Delivery: {{ $order->delivery_status }}
                                    </span>
                                @endif
                                <span class="badge bg-primary text-uppercase px-3 py-2">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            @if ($order->delivery_address)
                                <div class="alert alert-light border small py-2 mb-3">
                                    <strong>Delivery Address:</strong> {{ $order->delivery_address }}
                                    @if ($order->delivery_date)
                                        &bull; <strong>Date:</strong> {{ $order->delivery_date }}
                                    @endif
                                </div>
                            @endif

                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tag No</th>
                                            <th>Garment</th>
                                            <th>Service</th>
                                            <th>Colour</th>
                                            <th class="text-center">Qty</th>
                                            <th>Condition</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->garments as $garment)
                                            <tr>
                                                <td class="fw-semibold">{{ $garment->tag_no }}</td>
                                                <td>{{ $garment->garment_type }}</td>
                                                <td>{{ $garment->serviceItem->service_name ?? 'Laundry Service' }}</td>
                                                <td>{{ $garment->colour ?? '-' }}</td>
                                                <td class="text-center">{{ $garment->quantity }}</td>
                                                <td class="small text-muted">{{ $garment->condition_note ?? '-' }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $badgeClass = match ($garment->status) {
                                                            'collected' => 'bg-success',
                                                            'ready' => 'bg-info text-dark',
                                                            'in_process' => 'bg-warning text-dark',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} text-uppercase">
                                                        {{ str_replace('_', ' ', $garment->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Billing & M-Pesa Payment -->
                            <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                @if ($bill)
                                    <div>
                                        <span class="fw-bold">Bill #{{ $billId }}:</span>
                                        <span>Due: <strong>KES {{ number_format($billTotal, 2) }}</strong></span>
                                        <span class="ms-2">Balance: <strong>KES {{ number_format($billBalance, 2) }}</strong></span>
                                        <span class="badge {{ $paymentStatus === 'paid' ? 'bg-success' : 'bg-danger' }} ms-2 text-uppercase">
                                            {{ $paymentStatus }}
                                        </span>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @php
                                            $paidPayment = $bill->payments ? $bill->payments->where('status', 'paid')->first() : null;
                                        @endphp

                                        @if ($paidPayment && $paidPayment->receipt)
                                            <a href="{{ route('customer.receipts.show', $paidPayment->receipt->receipt_no) }}"
                                               class="btn btn-success btn-sm">
                                                View Receipt ({{ $paidPayment->receipt->receipt_no }})
                                            </a>
                                        @elseif ($paymentStatus !== 'paid' && $billBalance > 0)
                                            <form method="POST" action="{{ route('customer.mpesa.stk', $billId) }}" class="d-flex gap-2 m-0">
                                                @csrf
                                                <input type="text" name="phone_number" class="form-control form-control-sm"
                                                       value="{{ Auth::user()->phone ?? Auth::user()->phone_number ?? '' }}" placeholder="M-Pesa Phone" required>
                                                <button type="submit" class="btn btn-aqualine btn-sm text-nowrap">
                                                    Pay KES {{ number_format($billBalance, 0) }} via M-Pesa
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">Bill not yet generated by counter staff.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </section>

        <!-- Section 2: All Live Services & Rates -->
        <section id="all-services">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="fw-bold mb-0" style="color: #1B4F72;">All Aqualine Services &amp; Live Rates</h4>
                    <p class="text-muted small mb-0">Current prices synced directly from the Aqualine database.</p>
                </div>
                <a href="http://localhost/laundry-system/services.php" class="btn btn-outline-dark btn-sm">
                    View Detailed Service Descriptions &rarr;
                </a>
            </div>

            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover table-aqualine align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Billing Unit</th>
                                <th class="text-end">Unit Price (KES)</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $srv)
                                <tr>
                                    <td class="fw-semibold">{{ $srv->service_name }}</td>
                                    <td>{{ $srv->unit }}</td>
                                    <td class="text-end fw-bold" style="color: #1B4F72;">
                                        KES {{ number_format((float) $srv->unit_price, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('customer.delivery.form') }}" class="btn btn-aqualine btn-sm">
                                            Request Delivery
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No active services available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>