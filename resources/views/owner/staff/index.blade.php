<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - Aqualine Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="{{ route('owner.dashboard') }}">Aqualine Owner Portal ({{ Auth::user()->full_name }})</a>
        <div class="d-flex gap-2">
            <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-light btn-sm">&larr; Owner Dashboard</a>
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

        <div class="alert alert-info py-2 small">
            <strong>Single-Owner Policy:</strong> Only <strong>1</strong> owner account is allowed in the system (currently <strong>{{ $ownerCount }}</strong> owner record(s)). Any extra owner account is blocked from logging in and can be deleted here.
        </div>

        <div class="row g-4">
            <!-- Add Account -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Add Staff or Customer Account</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('owner.staff.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Account Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff (Counter Operations)</option>
                                    <option value="customer" {{ old('role') === 'customer' ? 'selected' : '' }}>Customer</option>
                                    @if (($ownerCount ?? 0) < 1)
                                        <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email (Optional)</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Initial Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>

<div class="mb-2">
    <label class="form-label" id="owner-username-label">Username</label>
    <input type="text" name="username" id="owner-username-input" class="form-control"
           value="{{ old('username') }}" placeholder="Required for staff; optional for customer" required>
</div>

            <!-- All Accounts (Owner, Staff, Customers) -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">All System Accounts ({{ $accounts->count() }})</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Login / Contact</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accounts as $acc)
                                    @php
                                        $isSelf = (int) $acc->user_id === (int) (Auth::user()->user_id ?? 0);
                                        $roleBadge = match ($acc->role) {
                                            'owner' => 'bg-dark',
                                            'staff' => 'bg-secondary',
                                            default => 'bg-info text-dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $acc->user_id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $acc->full_name }}</div>
                                            @if ($isSelf)
                                                <span class="badge bg-primary">You</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            <div>User: {{ $acc->username ?? '-' }}</div>
                                            <div>Phone: {{ $acc->phone }}</div>
                                            @if ($acc->email)
                                                <div class="text-muted">{{ $acc->email }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $roleBadge }} text-uppercase">{{ $acc->role }}</span></td>
                                        <td>
                                            <span class="badge {{ ($acc->status ?? 'active') === 'active' ? 'bg-success' : 'bg-danger' }} text-uppercase">
                                                {{ $acc->status ?? 'active' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                @unless ($isSelf)
                                                    <form method="POST" action="{{ route('owner.staff.toggle', $acc->user_id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm {{ ($acc->status ?? 'active') === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }} w-100">
                                                            {{ ($acc->status ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                @endunless

                                                <form method="POST" action="{{ route('owner.staff.reset-password', $acc->user_id) }}" class="d-flex gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="New PW" required>
                                                    <button type="submit" class="btn btn-sm btn-warning">Reset</button>
                                                </form>

                                                @unless ($isSelf)
                                                    <form method="POST" action="{{ route('owner.accounts.destroy', $acc->user_id) }}"
                                                          onsubmit="return confirm('Delete {{ $acc->role }} account for {{ addslashes($acc->full_name) }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger w-100">Delete Account</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>