<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام جرد المخازن') - كوميت</title>

    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: calc(100vh - 60px);
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            color: #2563eb;
        }
        .sidebar .nav-link.active {
            background-color: #2563eb;
            color: #fff;
        }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
        </div>
    </div>

    @auth
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ asset('images/comet-logo.png') }}" alt="COMET Logo" class="img-fluid" style="max-height: 40px;" onerror="this.style.display='none'">
                        <div class="d-none align-items-center justify-content-center bg-gradient text-white fw-bold px-3 py-1 rounded shadow-sm" style="background: linear-gradient(to bottom right, #d97706, #92400e);">
                            COMET
                        </div>
                    </div>
                    <h1 class="h5 fw-bold text-primary mb-0">نظام جرد المخازن</h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-medium text-dark">{{ Auth::user()->name }}</div>
                        <div class="small text-muted">
                            {{ Auth::user()->roles->pluck('display_name')->join('، ') }}
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            <span class="d-none d-md-inline">خروج</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="d-flex flex-grow-1">
        <!-- Sidebar -->
        <aside class="sidebar p-3" style="width: 250px; min-width: 250px;">
            <nav class="nav flex-column">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>
                    لوحة التحكم
                </a>
                @can('products.view')
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="bi bi-box me-2"></i>
                    المنتجات
                </a>
                @endcan
                @can('warehouses.view')
                <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                    <i class="bi bi-building me-2"></i>
                    المخازن
                </a>
                @endcan
                @can('inventory.view')
                <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                    <i class="bi bi-clipboard-data me-2"></i>
                    المخزون
                </a>
                @endcan
                @can('transactions.view')
                <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                    <i class="bi bi-arrow-left-right me-2"></i>
                    الحركات
                </a>
                @endcan
                <a class="nav-link {{ request()->routeIs('search.*') ? 'active' : '' }}" href="{{ route('search.index') }}">
                    <i class="bi bi-search me-2"></i>
                    البحث المتقدم
                </a>
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                    <i class="bi bi-file-text me-2"></i>
                    التقارير
                </a>
                @can('users.view')
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people me-2"></i>
                    المستخدمون
                </a>
                @endcan
                @can('roles.view')
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-check me-2"></i>
                    الأدوار والصلاحيات
                </a>
                @endcan
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-grow-1 p-4">
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
    @else
        @yield('content')
    @endauth

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global AJAX Setup -->
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Global AJAX error handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 401) {
                window.location.href = '{{ route("login") }}';
            } else if (xhr.status === 403) {
                showAlert('غير مصرح لك بهذا الإجراء', 'danger');
            } else if (xhr.status === 500) {
                showAlert('حدث خطأ في السيرفر', 'danger');
            }
        });

        // Utility functions
        function showAlert(message, type = 'info', duration = 5000) {
            const alertId = 'alert-' + Date.now();
            const alertHtml = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('body').append(alertHtml);

            if (duration > 0) {
                setTimeout(function() {
                    $('#' + alertId).fadeOut(function() {
                        $(this).remove();
                    });
                }, duration);
            }
        }

        function showLoading() {
            $('#loadingOverlay').css('display', 'flex');
        }

        function hideLoading() {
            $('#loadingOverlay').css('display', 'none');
        }

        // Global AJAX beforeSend and complete
        $(document).ajaxStart(function() {
            showLoading();
        }).ajaxStop(function() {
            hideLoading();
        });
    </script>

    @stack('scripts')
</body>
</html>

