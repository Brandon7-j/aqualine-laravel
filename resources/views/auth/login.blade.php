<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aqualine Laundry Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Aqualine Laundry System</h3>
                    <p class="text-muted">Web Based Laundry Management System</p>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <form method="POST" action="{{ route('login.submit') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="login_id" class="form-label">Phone number (customer) or username (staff, owner)</label>
                                <input type="text" class="form-control" id="login_id" name="login_id"
                                       value="{{ old('login_id') }}" placeholder="e.g. 0712 345 678 or admin" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Log in</button>
                        </form>

                        <div class="text-center mt-3">
                            <span class="small text-muted">New customer?</span>
                            <a href="{{ route('register') }}" class="small fw-semibold text-decoration-none">Create a Customer Account</a>
                        </div>

                        <p class="text-muted small mt-3 mb-0">
                            Forgot your password? Ask the shop staff (customer) or the owner (staff).
                        </p>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="http://localhost/laundry-system/index.php" class="text-decoration-none small">&larr; Back to Aqualine Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>