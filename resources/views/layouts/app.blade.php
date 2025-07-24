<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PBC Checklist System - @yield('title', 'Dashboard')</title>

    {{-- Bootstrap & FontAwesome --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Raleway:wght@600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Styling --}}
    <style>
    html, body {
        height: 100%;
        margin: 0;
        overflow: hidden;
        font-family: 'Open Sans', sans-serif;
        background-color: #f8fafc;
        color: #343a40;
    }

    /* Navbar */
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 80px;
        background-color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        z-index: 1050;
        display: flex;
        align-items: center;
    }

    .navbar-brand {
        font-family: 'Raleway', sans-serif;
        font-weight: 700;
        color: #326C79 !important;
        padding-left: 0 !important;
        margin-right: auto !important;
    }

    .navbar-brand img {
        height: 70px !important;
        width: auto;
        max-height: 100%;
        object-fit: contain;
    }

    .brand-title {
        font-family: 'Raleway', sans-serif;
        font-size: 1.3rem;
        color: #326C79;
    }

    .navbar .navbar-text,
    .navbar .btn-outline-light {
        color: #326C79 !important;
    }

    .navbar .btn-outline-light {
        border-color: #326C79 !important;
    }

    .navbar .btn-outline-light:hover {
        background-color: #326C79 !important;
        color: white !important;
    }

    .btn-danger {
        font-size: 0.85rem;
    }

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 80px; /* below navbar */
        left: 0;
        bottom: 0;
        width: 16.666667%; /* ~col-md-2 */
        overflow-y: auto;
        background-color: #eaf1f3;
        border-right: 1px solid #dee2e6;
        padding-top: 1rem;
        z-index: 1040;
    }

    .sidebar h6 {
        padding-left: 1rem;
        margin: 1rem 0 0.5rem;
        font-family: 'Raleway', sans-serif;
        font-size: 0.85rem;
        color: #326C79;
    }

    .nav-link {
        color: #495057;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        transition: background 0.2s, color 0.2s;
    }

    .nav-link i {
        width: 1.2rem;
    }

    .nav-link.active {
        background-color: #326C79 !important;
        color: #fff !important;
    }

    .nav-link:hover {
        color: #326C79;
    }

    .fw-bold {
        font-weight: 600 !important;
    }

    /* Main content */
    main {
        position: absolute;
        top: 80px;
        left: 16.666667%;
        right: 0;
        bottom: 0;
        overflow-y: auto;
        padding: 2rem;
        background-color: #ffffff;
        border-radius: 0.5rem 0 0 0;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.03);
    }

    .alert {
        font-size: 0.9rem;
    }

    .container-fluid > .row {
        height: 100%;
        margin-top: 0;
    }
</style>

</head>
<body>

    {{-- Navbar --}}
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-3 me-auto" href="#" style="padding: 0;">
    <img src="{{ asset('images/mtco-logo.png') }}" alt="PBC Logo" class="logo-img">
    <span class="fw-bold brand-title">PBC Checklist System</span>
</a>
        <div class="navbar-nav ms-auto align-items-center">
    @auth
        <span class="navbar-text me-3 text-dark">
    Welcome! {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})
</span>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-danger btn-sm">Logout</button>
        </form>
    @endauth
</div>
    </nav>

    {{-- Layout --}}
    <div class="container-fluid">
        <div class="row">

           {{-- Sidebar --}}
<nav class="col-md-2 sidebar py-4">
    @auth
        @if(auth()->user()->isAdmin())
            <h6>ADMIN MENU</h6>

            <h6>Main</h6>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.progress') ? 'active' : '' }}" href="{{ route('admin.progress') }}">
                        <i class="fas fa-chart-line me-2"></i>Progress
                    </a>
                </li>
            </ul>

            <h6>Management</h6>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                        <i class="fas fa-user-tie me-2"></i>Clients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}" href="{{ route('admin.projects.index') }}">
                        <i class="fas fa-briefcase me-2"></i>Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.pbc-templates.*') ? 'active' : '' }}" href="{{ route('admin.pbc-templates.index') }}">
                        <i class="fas fa-copy me-2"></i>Templates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.pbc-requests.*') ? 'active' : '' }}" href="{{ route('admin.pbc-requests.index') }}">
                        <i class="fas fa-envelope me-2"></i>PBC Requests
                    </a>
                </li>
            </ul>

            <h6>Documents</h6>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}" href="{{ route('admin.documents.index') }}">
                        <i class="fas fa-folder-open me-2"></i>Documents
                    </a>
                </li>
            </ul>

        @else
            <h6>CLIENT MENU</h6>

            <h6>Main</h6>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.progress') ? 'active' : '' }}" href="{{ route('client.progress') }}">
                        <i class="fas fa-chart-line me-2"></i>Progress
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.pbc-requests.*') ? 'active' : '' }}" href="{{ route('client.pbc-requests.index') }}">
                        <i class="fas fa-envelope me-2"></i>PBC Requests
                    </a>
                </li>
            </ul>

            <h6>Documents</h6>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.documents.*') ? 'active' : '' }}" href="{{ route('client.documents.index') }}">
                        <i class="fas fa-folder-open me-2"></i>Documents
                    </a>
                </li>
            </ul>

        @endif
    @endauth
</nav>

            {{-- Main Content --}}
            <main class="col-md-10 ms-sm-auto px-4 py-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')

</body>
</html>
