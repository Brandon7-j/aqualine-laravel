<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer - {{ $customer->full_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary px-4 mb-4">
        <a class="navbar-brand fw-bold" href="{{ route('staff.dashboard') }}">Aqualine Staff Counter</a>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.dashboard') }}" class="btn btn-outline-light btn-sm">&larr; Back to Dashboard</a>
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
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <!-- Edit Customer Profile -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Edit Customer (#{{ $customer->user_id }})</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.customers.update', $customer->user_id) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="{{ old('full_name', $customer->full_name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone', $customer->phone) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email (Optional)</label>
                                <input type="email" name="email" class="form-control"
                                       value="{{ old('email', $customer->email) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Account Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" {{ ($customer->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ ($customer->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Reset Customer Password -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Reset Customer Password</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.customers.reset-password', $customer->user_id) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                            </div>

                            <button type="submit" class="btn btn-warning">Reset Password</button>
                        </form>
                    </div>
                </div>

                <!-- Remove Fake / Unused Account -->
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-white fw-bold text-danger">Remove Customer Account</div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Use this only to remove a fake or duplicate customer account that has no recorded orders.
                        </p>
                        <form method="POST" action="{{ route('staff.customers.destroy', $customer->user_id) }}"
                              onsubmit="return confirm('Remove customer {{ addslashes($customer->full_name) }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Remove Customer</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Customer Orders History -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Orders for {{ $customer->full_name }} ({{ $customerOrders->count() }})</div>
                    <div class="card-body">
                        @if ($customerOrders->isEmpty())
                            <p class="text-muted small mb-0">No orders recorded for this customer yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Received</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customerOrders as $ord)
                                            <tr>
                                                <td class="fw-bold">#{{ $ord->order_no }}</td>
                                                <td class="small">{{ $ord->date_received }}</td>
                                                <td><span class="badge bg-info text-dark text-uppercase">{{ str_replace('_', ' ', $ord->status) }}</span></td>
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
</body>
</html>