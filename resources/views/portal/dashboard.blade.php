@extends('layouts.portal')

@section('content')
<section class="page-header portal-page-header">
    <h1>Dashboard Pelanggan</h1>
    <p>Selamat datang, {{ auth()->user()->name }}</p>
</section>

<div class="portal-stats-grid">
    <div class="portal-stat-card">
        <span>Total Poin</span>
        <h2>{{ $customer->points_balance ?? 0 }}</h2>
        <p>Poin loyalitas Anda</p>
    </div>

    <div class="portal-stat-card">
        <span>Order Aktif</span>
        <h2>{{ $activeOrders->count() }}</h2>
        <p>Cucian sedang diproses</p>
    </div>

    <div class="portal-stat-card">
        <span>Order Selesai</span>
        <h2>{{ $completedOrders->count() }}</h2>
        <p>Riwayat cucian selesai</p>
    </div>
</div>

<div class="portal-profile-card">
    <h3>Informasi Pelanggan</h3>

    <div class="portal-profile-row">
        <span>Nama</span>
        <strong>{{ auth()->user()->name }}</strong>
    </div>

    <div class="portal-profile-row">
        <span>Nomor Telepon</span>
        <strong>{{ $customer->phone ?? '-' }}</strong>
    </div>

    <div class="portal-profile-row">
        <span>Alamat</span>
        <strong>{{ $customer->address ?? '-' }}</strong>
    </div>
</div>

<div class="portal-pickup-card" style="display: flex">
    <div>
        <h3>Ajukan Jemput Cucian</h3>
        <p>Buat permintaan agar cucian Anda dijemput oleh staff laundry.</p>
    </div>

    <a href="{{ route('portal.pickups.create') }}" class="portal-pickup-btn" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        + Buat Pesanan
    </a>
</div>

<div class="portal-order-card">
    <h3>Riwayat Order Terbaru</h3>

    <table class="portal-order-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Layanan</th>
                <th>Total</th>
                <th>Status Cucian</th>
                <th>Status Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($recentOrders as $order)
                <tr>
                    <td>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $order->service->name ?? '-' }}</td>
                    <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    <td>
                        <span class="portal-status-badge">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td>
                        <span class="portal-payment-badge {{ $order->payment_status === 'dibayar' ? 'paid' : 'unpaid' }}">
                            {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('portal.orders.show', $order) }}" class="portal-detail-link">
                            Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">Belum ada order laundry.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
