<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $receipt->receipt_no }} - Aqualine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="bg-light py-4">
    <div class="container" style="max-width: 650px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div>
                        <h4 class="fw-bold mb-0">AQUALINE LAUNDRY</h4>
                        <small class="text-muted">Official Payment Receipt</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success fs-6">PAID</span>
                        <div class="small mt-1"><strong>Receipt #:</strong> {{ $receipt->receipt_no }}</div>
                        <div class="small text-muted">Date: {{ $receipt->issued_at }}</div>
                    </div>
                </div>

                <div class="row mb-3 small">
                    <div class="col-6">
                        <strong>Customer:</strong> {{ $receipt->bill->order->customer->full_name }}<br>
                        <strong>Phone:</strong> {{ $receipt->bill->order->customer->phone }}
                    </div>
                    <div class="col-6 text-end">
                        <strong>Order #:</strong> #{{ $receipt->bill->order->order_no }}<br>
                        <strong>Received:</strong> {{ $receipt->bill->order->date_received }}
                    </div>
                </div>

                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Tag #</th>
                            <th>Item &amp; Service</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receipt->bill->order->garments as $g)
                            <tr>
                                <td>{{ $g->tag_no }}</td>
                                <td>{{ $g->garment_type }} ({{ $g->service->service_name }})</td>
                                <td class="text-center">{{ $g->quantity }}</td>
                                <td class="text-end">{{ number_format($g->service->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($g->quantity * $g->service->unit_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total Paid (KES)</th>
                            <th class="text-end">KES {{ number_format($receipt->bill->total_amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>

                <div class="small text-muted mb-3">
                    <strong>Payments Recorded:</strong>
                    @foreach ($receipt->bill->payments as $p)
                        <div>- KES {{ number_format($p->amount, 2) }} via {{ strtoupper($p->payment_method) }} {{ $p->mpesa_ref ? "(Ref: {$p->mpesa_ref})" : '' }}</div>
                    @endforeach
                </div>

                <div class="text-center no-print">
                    <button onclick="window.print()" class="btn btn-dark">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>