<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Customer Account - Aqualine Laundry System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Aqualine Laundry System</h3>
                    <p class="text-muted">Create a Customer Tracking Account</p>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <form method="POST" action="{{ route('register.submit') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="{{ old('full_name') }}" placeholder="e.g. Brian Kamau" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number (Login ID)</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="{{ old('phone') }}" placeholder="e.g. 0116558729" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (Optional)</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="{{ old('email') }}" placeholder="e.g. brian@example.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password (min 6 characters)</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Create Account</button>
                        </form>

                        <div class="text-center mt-3">
                            <span class="small text-muted">Already have an account?</span>
                            <a href="{{ route('login') }}" class="small fw-semibold text-decoration-none">Log in here</a>
                        </div>
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