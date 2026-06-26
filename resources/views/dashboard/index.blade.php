@extends('layouts.app')

@section('content')
    <section class="page-header">
        <h1>Dashboard</h1>
        <p>Selamat datang di Sistem Manajemen Laundry</p>
    </section>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <h2>{{ $stats['total_customers'] }}</h2>
            <p>Total Pelanggan</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">▣</div>
            <h2>{{ $stats['today_transactions'] }}</h2>
            <p>Transaksi Hari Ini</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">◇</div>
            <h2>{{ $stats['active_orders'] }}</h2>
            <p>Order Aktif</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">↗</div>
            <h2>{{ $stats['monthly_income'] }}</h2>
            <p>Pendapatan Bulan Ini</p>
        </div>
    </section>

    <section class="chart-card">
        <h3>Pendapatan Minggu Ini</h3>

        @php
            $maxRevenue = collect($weeklyRevenue)->max('value') ?: 1;
        @endphp

        <div class="dashboard-bar-chart">
            @foreach($weeklyRevenue as $item)
                @php
                    $height = $item['value'] > 0
                        ? max(8, ($item['value'] / $maxRevenue) * 100)
                        : 0;
                @endphp

                <div class="dashboard-bar-item">
                    <div class="dashboard-bar-track">
                        <div class="dashboard-bar" style="height: {{ $height }}%;"></div>
                    </div>

                    <span>{{ $item['day'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="quick-section">
        <h3>Aksi Cepat</h3>

        <div class="quick-grid">
            <a href="{{ route('orders.index') }}" class="quick-card quick-primary">
                <div class="quick-icon">+</div>
                <div>
                    <h4>Buat Transaksi Baru</h4>
                    <p>Tambah order laundry baru</p>
                </div>
            </a>

            <a href="{{ route('customers.index') }}" class="quick-card">
                <div class="quick-icon muted">⌕</div>
                <div>
                    <h4>Cari Pelanggan</h4>
                    <p>Temukan data pelanggan</p>
                </div>
            </a>
        </div>
    </section>
@endsection
