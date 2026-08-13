<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Vouchering Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container" style="max-width: 420px">
        <div class="card shadow-sm"><div class="card-body p-4">
            <h1 class="h3 mb-4">Vouchering Admin</h1>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" required></div>
                <div class="form-check mb-3"><input class="form-check-input" id="remember" name="remember" type="checkbox" value="1"><label class="form-check-label" for="remember">Remember me</label></div>
                <button class="btn btn-primary w-100">Sign in</button>
            </form>
        </div></div>
    </main>
</body>
</html>
