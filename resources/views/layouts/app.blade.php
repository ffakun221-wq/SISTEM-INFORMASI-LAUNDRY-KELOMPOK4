<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Laundry</title>
    <link rel="stylesheet" href="{{ asset('css/laundry.css') }}">
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-logo">L</div>
                <span>Laundry System</span>
            </div>

            <nav class="sidebar-menu">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon">▦</span>
                    Dashboard
                </a>

                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    Manajemen Pelanggan
                </a>

                <a href="{{ route('services.index') }}" class="{{ request()->routeIs('services.*') ? 'active' : '' }}">
                    Manajemen Layanan
                </a>

                <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    Manajemen Transaksi
                </a>

                <a href="{{ route('tracking.index') }}" class="{{ request()->routeIs('tracking.*') ? 'active' : '' }}">
                    Order Tracking
                </a>

                <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                    Pembayaran
                </a>

                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        Laporan Keuangan
                    </a>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="logout-area">
                @csrf
                <button type="submit">
                    <span class="menu-icon">↪</span>
                    Keluar
                </button>
            </form>
        </aside>

        <main class="main-content">
            @yield('content')
        </main>
    </div>
</body>
</html>
