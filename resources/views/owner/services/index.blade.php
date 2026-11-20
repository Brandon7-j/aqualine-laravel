<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services &amp; Pricing - Aqualine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4">
        <a class="navbar-brand fw-bold" href="{{ route('owner.dashboard') }}">Aqualine Owner Portal</a>
        <div class="d-flex gap-2">
            <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-light btn-sm">Reports Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Log out</button>
            </form>
        </div>
    </nav>

    <div class="container py-4">
        <h4 class="mb-3">Laundry Services &amp; Price Catalog </h4>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <!-- FR08: Add New Service -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold bg-white">Add New Laundry Service</div>
            <div class="card-body">
                <form method="POST" action="{{ route('owner.services.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">Service Name</label>
                        <input type="text" name="service_name" class="form-control" placeholder="e.g. Express Ironing" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Billing Unit</label>
                        <input type="text" name="unit" class="form-control" placeholder="e.g. Per Piece or Per Kg" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit Price (KES)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="100.00" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-dark w-100">Add Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FR09: Update Pricing & Status -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold bg-white">Existing Services</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Service Name</th>
                            <th>Unit</th>
                            <th>Unit Price (KES)</th>
                            <th>Availability</th>
                            <th>Save Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $srv)
                            <tr>
                                <form method="POST" action="{{ route('owner.services.update', $srv->service_id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <td class="fw-bold">{{ $srv->service_name }}</td>
                                    <td>{{ $srv->unit }}</td>
                                    <td style="width: 170px;">
                                        <input type="number" step="0.01" name="unit_price" class="form-control form-control-sm" value="{{ $srv->unit_price }}" required>
                                    </td>
                                    <td style="width: 160px;">
                                        <select name="is_active" class="form-select form-select-sm">
                                            <option value="1" {{ $srv->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ !$srv->is_active ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>