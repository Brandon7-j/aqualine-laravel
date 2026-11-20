<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Delivery — Aqualine Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #F8FAFC;
            --brand-primary: #1B4F72;
            --accent-cta: #0F766E;
            --accent-cta-hover: #0B5E58;
            --text-dark: #1F2937;
            --border-subtle: #E2E8F0;
        }
        body {
            background-color: var(--bg-canvas);
            color: var(--text-dark);
        }
        .portal-nav {
            background-color: var(--brand-primary);
            border-bottom: 3px solid var(--accent-cta);
        }
        .btn-accent {
            background-color: var(--accent-cta);
            border-color: var(--accent-cta);
            color: #FFFFFF;
            font-weight: 600;
        }
        .btn-accent:hover {
            background-color: var(--accent-cta-hover);
            border-color: var(--accent-cta-hover);
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark portal-nav mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold" href="{{ route('customer.dashboard') }}">
                Aqualine Customer Portal ({{ $customer->full_name ?? Auth::user()->full_name }})
            </a>
            <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-light btn-sm">
                &larr; Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container pb-5">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold py-3" style="color: var(--brand-primary);">
                        Request Delivery on an Order
                    </div>
                    <div class="card-body">
                        @if ($orders->isEmpty())
                            <div class="alert alert-info mb-3">
                                You do not currently have any order
                            </div>
                            <p class="text-muted small mb-3">
                                Once counter staff logs your laundry order, you can select it here to request doorstep delivery.
                            </p>
                            <a href="{{ route('customer.dashboard') }}" class="btn btn-accent">
                                &larr; Return to Customer Dashboard
                            </a>
                        @else
                            <form action="{{ route('customer.delivery.store') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="order_no" class="form-label fw-semibold">Select Order</label>
                                    <select name="order_no" id="order_no" class="form-select" required>
                                        @foreach ($orders as $order)
                                            <option value="{{ $order->order_no }}" {{ (string) old('order_no') === (string) $order->order_no ? 'selected' : '' }}>
                                                Order #{{ $order->order_no }} — {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="delivery_address" class="form-label fw-semibold">Delivery Address</label>
                                    <input type="text" name="delivery_address" id="delivery_address" class="form-control"
                                           value="{{ old('delivery_address') }}"
                                           placeholder="e.g. Madaraka Estate, Court B, House 14" required>
                                </div>

                                <div class="mb-3">
                                    <label for="delivery_date" class="form-label fw-semibold">Preferred Delivery Date</label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="form-control"
                                           min="{{ now()->toDateString() }}"
                                           value="{{ old('delivery_date', now()->toDateString()) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="delivery_notes" class="form-label fw-semibold">Delivery Notes (Optional)</label>
                                    <textarea name="delivery_notes" id="delivery_notes" rows="2" class="form-control"
                                              placeholder="Gate code, landmark, or call on arrival...">{{ old('delivery_notes') }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-accent w-100">Save Delivery Request</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold py-3" style="color: var(--brand-primary);">
                        Your Orders Delivery Status
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order</th>
                                    <th>Address &amp; Date</th>
                                    <th>Delivery Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="fw-semibold">#{{ $order->order_no }}</td>
                                        <td class="small">
                                            @if ($order->delivery_address)
                                                <div>{{ $order->delivery_address }}</div>
                                                <div class="text-muted">{{ $order->delivery_date }}</div>
                                            @else
                                                <span class="text-muted">Not requested yet</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->delivery_status)
                                                <span class="badge bg-info text-dark">{{ strtoupper($order->delivery_status) }}</span>
                                            @else
                                                <span class="badge bg-secondary">NONE</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">You do not currently have any order</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>