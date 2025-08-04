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

    /* Reminder badge styles */
    .reminder-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7rem;
        font-weight: bold;
        min-width: 18px;
        text-align: center;
        animation: pulse-badge 2s infinite;
    }

    @keyframes pulse-badge {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
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
        position: relative;
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

    /* Styles for custom sections */
    @yield('styles')
</style>

</head>
<body>
@if(session('upload_config_warnings') && auth()->user()->isSystemAdmin())
<div class="alert alert-warning alert-dismissible fade show position-fixed"
     style="top: 20px; right: 20px; z-index: 9999; min-width: 400px; max-width: 500px;">
    <div class="d-flex align-items-start">
        <i class="fas fa-exclamation-triangle me-2 mt-1 text-warning"></i>
        <div class="flex-grow-1">
            <h6 class="alert-heading mb-2">
                <i class="fas fa-server me-1"></i>
                PHP Configuration Issues Detected
            </h6>
            <div class="mb-2">
                <small class="text-muted">The following settings may cause issues with 300MB file uploads:</small>
            </div>
            <ul class="mb-2 small">
                @foreach(session('upload_config_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <div class="border-top pt-2 mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Contact your system administrator to update these PHP settings.
                    <a href="{{ route('admin.upload.test') }}" class="text-decoration-none ms-2" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Test Results
                    </a>
                </small>
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Upload Stats Dashboard Widget (Optional - for admin dashboard) -->
@if(request()->routeIs('admin.dashboard') && auth()->user()->isSystemAdmin())
<div id="uploadStatsWidget" class="position-fixed"
     style="bottom: 20px; right: 20px; z-index: 1000; width: 300px; display: none;">
    <div class="card border-info">
        <div class="card-header bg-info text-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="fw-bold">
                    <i class="fas fa-upload me-1"></i>
                    Upload System Status
                </small>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="hideUploadStats()"></button>
            </div>
        </div>
        <div class="card-body p-3">
            <div id="uploadStatsContent">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <small class="d-block mt-2 text-muted">Loading upload statistics...</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-load upload stats on admin dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/admin/dashboard')) {
        setTimeout(loadUploadStats, 2000); // Load after 2 seconds
    }
});

function loadUploadStats() {
    fetch('/admin/upload/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUploadStats(data);
                document.getElementById('uploadStatsWidget').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Failed to load upload stats:', error);
        });
}

function displayUploadStats(data) {
    const content = document.getElementById('uploadStatsContent');
    const stats = data.upload_stats;
    const server = data.server_stats;

    content.innerHTML = `
        <div class="row g-2 text-center">
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fw-bold text-primary">${stats.total_uploads}</div>
                    <small class="text-muted">Total Files</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fw-bold text-success">${stats.total_size_formatted}</div>
                    <small class="text-muted">Total Size</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fw-bold text-warning">${stats.large_files_count}</div>
                    <small class="text-muted">Large Files</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <div class="fw-bold text-info">${stats.recent_uploads}</div>
                    <small class="text-muted">Recent</small>
                </div>
            </div>
        </div>

        ${server.disk_usage_percentage > 80 ? `
        <div class="alert alert-warning py-2 mt-2 mb-0">
            <small>
                <i class="fas fa-exclamation-triangle me-1"></i>
                Disk usage: ${server.disk_usage_percentage.toFixed(1)}%
            </small>
        </div>
        ` : ''}

        ${data.configuration_issues.length > 0 ? `
        <div class="alert alert-danger py-2 mt-2 mb-0">
            <small>
                <i class="fas fa-exclamation-circle me-1"></i>
                ${data.configuration_issues.length} config issue(s) detected
            </small>
        </div>
        ` : ''}
    `;
}

function hideUploadStats() {
    document.getElementById('uploadStatsWidget').style.display = 'none';
}
</script>
@endif

<style>
/* Enhanced alert styling */
.alert.position-fixed {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: none;
    border-radius: 8px;
}

.alert-warning.position-fixed {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #f39c12;
}

/* Upload stats widget styling */
#uploadStatsWidget .card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#uploadStatsWidget .border {
    border-color: #e9ecef !important;
}

#uploadStatsWidget .text-primary { color: #007bff !important; }
#uploadStatsWidget .text-success { color: #28a745 !important; }
#uploadStatsWidget .text-warning { color: #ffc107 !important; }
#uploadStatsWidget .text-info { color: #17a2b8 !important; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .alert.position-fixed {
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }

    #uploadStatsWidget {
        right: 10px;
        bottom: 10px;
        width: calc(100% - 20px);
        max-width: 300px;
    }
}
</style>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-3 me-auto" href="#" style="padding: 0;">
                <img src="{{ asset('images/mtco-logo.png') }}" alt="PBC Logo" class="logo-img">
                <span class="fw-bold brand-title">PBC Checklist System</span>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                @auth
                    {{-- Reminder Bell for Clients --}}
                    @if(auth()->user()->isClient())
                        <div class="me-3 position-relative">
                            <a href="{{ route('client.reminders.index') }}" class="btn btn-outline-secondary btn-sm position-relative">
                                <i class="fas fa-bell"></i>
                                <span id="navbar-reminder-badge" class="reminder-badge" style="display: none;">
                                    <span id="navbar-reminder-count">0</span>
                                </span>
                            </a>
                        </div>
                    @endif

                    <span class="navbar-text me-3 text-dark">
                        Welcome! {{ auth()->user()->name }} ({{ auth()->user()->getRoleDisplayName() }})
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button class="btn btn-danger btn-sm">Logout</button>
                    </form>
                @endauth
            </div>
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
                @if(auth()->user()->canCreateUsers())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                    </li>
                @endif
                @if(auth()->user()->canManageClients())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                            <i class="fas fa-user-tie me-2"></i>Clients
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}" href="{{ route('admin.projects.index') }}">
                        <i class="fas fa-briefcase me-2"></i>Projects
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
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.reminders.*') ? 'active' : '' }} position-relative" href="{{ route('client.reminders.index') }}">
                        <i class="fas fa-bell me-2"></i>Reminders
                        <span id="sidebar-reminder-badge" class="reminder-badge" style="display: none;">
                            <span id="sidebar-reminder-count">0</span>
                        </span>
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
