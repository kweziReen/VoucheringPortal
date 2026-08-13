<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vouchering Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Vouchering Admin</a>
            <div class="d-flex align-items-center gap-3"><span class="navbar-text text-white">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Sign out</button></form></div>
        </div>
    </nav>
    <main class="container pb-5">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</body>
</html>
