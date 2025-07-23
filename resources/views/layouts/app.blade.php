<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PBC System - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">PBC System</a>
            <div class="navbar-nav ms-auto">
                @auth
                    <span class="navbar-text me-3">{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-light sidebar py-3">
                @auth
                    @if(auth()->user()->isAdmin())
                        <h6 class="text-muted mb-3">ADMIN MENU</h6>
                        <ul class="nav flex-column">
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Users</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.clients.index') }}">Clients</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.projects.index') }}">Projects</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.pbc-templates.index') }}">Templates</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.pbc-requests.index') }}"><strong>PBC Requests</strong></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.documents.index') }}">Documents</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.progress') }}">Progress</a></li>
                        </ul>
                    @else
                        <h6 class="text-muted mb-3">CLIENT MENU</h6>
                        <ul class="nav flex-column">
                            <li class="nav-item"><a class="nav-link" href="{{ route('client.dashboard') }}">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('client.pbc-requests.index') }}"><strong>PBC Requests</strong></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('client.documents.index') }}">Documents</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('client.progress') }}">Progress</a></li>
                        </ul>
                    @endif
                @endauth
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-4 py-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
