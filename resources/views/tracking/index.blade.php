@extends('layouts.app')

@section('content')
<section class="page-header tracking-page-header">
    <h1>Order Tracking</h1>
    <p>Pantau dan update status order laundry</p>
</section>

@if(session('success'))
    <div class="alert-success" style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
        {{ session('success') }}
    </div>
@endif

@if(session('info'))
    <div class="alert-info" style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #fef08a; color: #854d0e; border: 1px solid #fde047; font-weight: 600;">
        ⚠️ {{ session('info') }}
    </div>
@endif

<div class="customer-toolbar" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('tracking.index') }}" style="display: flex; gap: 8px;">
        <div style="position: relative; flex: 1; max-width: 400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Pelanggan atau ID Pesanan..." style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; outline: none;">
        </div>
        <button type="submit" style="height: 42px; background: #0f172a; color: white; border: none; border-radius: 8px; padding: 0 20px; font-weight: 600; cursor: pointer;">Cari</button>
        @if(request('search'))
            <a href="{{ route('tracking.index') }}" style="height: 42px; display: inline-flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 16px; color: #64748b; text-decoration: none; font-weight: 600; background: #f8fafc;">Reset</a>
        @endif
    </form>
</div>

<div class="tracking-table-card">
    <table class="tracking-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Nama Pelanggan</th>
                <th>Layanan</th>
                <th>Total Harga</th>
                <th>Tanggal Masuk</th>
                <th>Estimasi Selesai</th>
                <th>Status Saat Ini</th>
                <th>Update Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse($orders as $order)
                @php
                    $currentStatus = $order->status;
                    $statusLabel = $statusOptions[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));

                    $statusClass = match($currentStatus) {
                        'diterima' => 'status-masuk',
                        'dicuci' => 'status-cuci',
                        'dijemur' => 'status-kering',
                        'disetrika' => 'status-setrika',
                        'siap_diambil' => 'status-ambil',
                        'selesai' => 'status-selesai',
                        default => 'status-masuk',
                    };

                    $estimatedHours = $order->service->estimated_hours ?? 48;
                    $estimatedDate = $order->created_at
                        ? $order->created_at->copy()->addHours($estimatedHours)->format('d M Y')
                        : '-';
                @endphp

                <tr>
                    <td><strong>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $order->customer->user->name ?? '-' }}</td>
                    <td>{{ $order->service->name ?? '-' }}</td>
                    <td><strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></td>
                    <td>{{ $order->created_at ? $order->created_at->format('d M Y') : '-' }}</td>
                    <td>{{ $estimatedDate }}</td>
                    <td>
                        <span class="tracking-status-badge {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('tracking.update', $order) }}">
                            @csrf
                            @method('PATCH')

                            <select name="status" class="tracking-status-select" onchange="this.form.submit()">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-row">
                        Belum ada order laundry.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="tracking-legend-card">
    <p>Status Order:</p>

    <div class="tracking-legend-list">
        <span class="tracking-status-badge status-masuk">Masuk</span>
        <span class="tracking-status-badge status-cuci">Sedang Dicuci</span>
        <span class="tracking-status-badge status-kering">Pengeringan</span>
        <span class="tracking-status-badge status-setrika">Setrika</span>
        <span class="tracking-status-badge status-ambil">Siap Diambil</span>
        <span class="tracking-status-badge status-selesai">Selesai</span>
    </div>
</div>
@endsection
