<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Profile - Aqualine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary px-4">
        <a class="navbar-brand fw-bold" href="{{ route('staff.dashboard') }}">← Back to Staff Counter</a>
    </nav>

    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-4">
            <!-- FR11: Update Customer Record -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">Update Customer Details</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.customers.update', $customer->user_id) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-2">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="{{ $customer->full_name }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $customer->email }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- FR07: Reset Customer Password -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">Reset Customer Password </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.customers.reset', $customer->user_id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">New Password for {{ $customer->full_name }}</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                            </div>
                            <button type="submit" class="btn btn-warning">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- FR12: Customer Order History -->
        <div class="card shadow-sm mt-4">
            <div class="card-header fw-bold">Order History for {{ $customer->full_name }}</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Date Received</th>
                            <th>Garments</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>#{{ $order->order_no }}</td>
                                <td>{{ $order->date_received }}</td>
                                <td>{{ $order->garments->count() }} item(s)</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>