<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pelanggan - Sistem Laundry</title>
    <link rel="stylesheet" href="{{ asset('css/laundry.css') }}">
</head>
<body class="portal-body">
    <div class="portal-shell">
        <aside class="portal-sidebar">
            <div class="brand">
                <div class="brand-logo">L</div>
                <span>Laundry System</span>
            </div>

            <nav class="sidebar-menu">
                <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                    <span class="menu-icon">🖥️</span>
                    Dashboard
                </a>
                <a href="{{ route('portal.pickups.create') }}" class="{{ request()->routeIs('portal.pickups.create') ? 'active' : '' }}">
                    <span class="menu-icon">➕</span>
                    Buat Pesanan
                </a>
                <a href="{{ route('portal.active') }}" class="{{ request()->routeIs('portal.active') ? 'active' : '' }}">
                    <span class="menu-icon">📦</span>
                    Pesanan Aktif
                </a>
                <a href="{{ route('portal.history') }}" class="{{ request()->routeIs('portal.history') ? 'active' : '' }}">
                    <span class="menu-icon">📜</span>
                    Riwayat
                </a>
                <a href="{{ route('portal.points') }}" class="{{ request()->routeIs('portal.points') ? 'active' : '' }}">
                    <span class="menu-icon">⭐</span>
                    Poin Saya
                </a>
                <a href="{{ route('portal.account') }}" class="{{ request()->routeIs('portal.account') ? 'active' : '' }}">
                    <span class="menu-icon">👤</span>
                    Manajemen Akun
                </a>
                <a href="{{ route('portal.faq') }}" class="{{ request()->routeIs('portal.faq') ? 'active' : '' }}">
                    <span class="menu-icon">?</span>
                    FAQ
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="logout-area">
                @csrf
                <button type="submit">
                    <span class="menu-icon">↪</span>
                    Keluar
                </button>
            </form>
        </aside>

        <main class="portal-main-content">
            @yield('content')
        </main>
    </div>
</body>
</html>
