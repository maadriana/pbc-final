<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PBC Checklist Login</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Raleway:wght@600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .login-card h2 {
            font-family: 'Raleway', sans-serif;
            color: #326C79;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: #326C79;
            border-color: #326C79;
        }

        .btn-primary:hover {
            background-color: #289DD2;
            border-color: #289DD2;
        }

        .logo {
            display: block;
            margin: 0 auto 1rem;
            height: 100px;
        }

        .text-small {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center">
            <img src="{{ asset('images/mtco-logo.png') }}" alt="MTCO Logo" class="logo">
            <h2 class="mb-4">PBC Checklist</h2>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>

            @if (Route::has('password.request'))
                <div class="text-center text-small">
                    <a href="{{ route('password.request') }}" class="text-decoration-none text-muted">Forgot your password?</a>
                </div>
            @endif
        </form>
    </div>

</body>
</html>
