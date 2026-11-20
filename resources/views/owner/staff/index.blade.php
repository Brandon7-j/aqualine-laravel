<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff &amp; Owner Accounts — Aqualine Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('owner.dashboard') }}">Aqualine Owner Portal</a>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-light btn-sm">Reports Dashboard</a>
                <a href="{{ route('owner.services.index') }}" class="btn btn-outline-light btn-sm">Services &amp; Pricing</a>
                <a href="{{ route('owner.staff.index') }}" class="btn btn-light btn-sm fw-semibold text-primary">Staff &amp; Owners</a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Create Staff or Owner Form -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">
                        Add Staff or Owner Account
                    </div>
                    <div class="card-body">
                        <form action="{{ route('owner.staff.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" name="full_name" id="full_name" class="form-control"
                                       value="{{ old('full_name') }}" placeholder="e.g. Grace Wanjiku" required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Account Role</label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff (Counter Operations)</option>
                                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner (Full Management)</option>
                                </select>
                            </div>

                            @if ($hasUsername)
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" name="username" id="username" class="form-control"
                                           value="{{ old('username') }}" placeholder="e.g. grace_owner" required>
                                </div>
                            @endif

                            @if ($phoneCol)
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                           value="{{ old('phone') }}" placeholder="e.g. 0712345678" required>
                                </div>
                            @endif

                            @if ($hasEmail)
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-muted small">(Optional)</span></label>
                                    <input type="email" name="email" id="email" class="form-control"
                                           value="{{ old('email') }}" placeholder="name@aqualine.co.ke">
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="password" class="form-label">Initial Password</label>
                                <input type="password" name="password" id="password" class="form-control"
                                       minlength="6" placeholder="Minimum 6 characters" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Accounts List Table -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Staff &amp; Owner Accounts ({{ $accounts->count() }})</span>
                    </div>
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
                                @forelse ($accounts as $account)
                                    @php
                                        $isActive = $activeCol === 'is_active'
                                            ? (bool) $account->is_active
                                            : ($activeCol === 'status' ? strtolower((string) $account->status) === 'active' : true);
                                        $isCurrentUser = (int) $account->user_id === (int) auth()->id();
                                    @endphp
                                    <tr>
                                        <td>{{ $account->user_id }}</td>
                                        <td class="fw-semibold">
                                            {{ $account->full_name }}
                                            @if ($isCurrentUser)
                                                <span class="badge bg-info text-dark ms-1">You</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($hasUsername && !empty($account->username))
                                                <div><strong>User:</strong> {{ $account->username }}</div>
                                            @endif
                                            @if ($phoneCol && !empty($account->{$phoneCol}))
                                                <div><strong>Phone:</strong> {{ $account->{$phoneCol} }}</div>
                                            @endif
                                            @if ($hasEmail && !empty($account->email))
                                                <div class="text-muted">{{ $account->email }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $account->role === 'owner' ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ strtoupper($account->role) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $isActive ? 'bg-success' : 'bg-danger' }}">
                                                {{ $isActive ? 'ACTIVE' : 'INACTIVE' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                @if (!$isCurrentUser && $activeCol)
                                                    <form action="{{ route('owner.staff.toggle', $account->user_id) }}" method="POST" class="m-0">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm {{ $isActive ? 'btn-outline-danger' : 'btn-outline-success' }} w-100">
                                                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                @elseif ($isCurrentUser)
                                                    <span class="text-muted small">Active Owner Session</span>
                                                @endif

                                                <form action="{{ route('owner.staff.reset', $account->user_id) }}" method="POST" class="d-flex gap-1 m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="new_password" class="form-control form-control-sm"
                                                           placeholder="Default: Aqualine@123" minlength="6">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning text-dark text-nowrap">
                                                        Reset PW
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No staff or owner accounts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>