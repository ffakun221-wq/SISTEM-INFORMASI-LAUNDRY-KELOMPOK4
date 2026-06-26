@extends('layouts.auth')

@section('content')
<div class="auth-card" style="max-width: 760px; width: 100%;">
    <div class="logo-box">L</div>

    <h1 class="auth-title">Cek Status Cucian</h1>
    <p class="auth-subtitle">Masukkan kode invoice yang tertera pada nota laundry.</p>

    @if($errors->any())
        <div style="margin-bottom: 14px; padding: 10px; border-radius: 8px; background: #fee2e2; color: #991b1b; font-size: 14px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('status.search') }}" style="margin-bottom: 18px;">
        @csrf
        <div class="form-group">
            <label>Kode Invoice</label>
            <input
                type="text"
                name="invoice_code"
                class="form-control"
                placeholder="Contoh: INV-20260619123456"
                value="{{ old('invoice_code', $invoiceCode) }}"
                required
            >
        </div>

        <button type="submit" class="btn btn-primary">Cek Status</button>
        <a href="{{ route('login') }}" class="btn btn-outline">Login Admin/Kasir</a>
    </form>

    @if($searched && !$invoice)
        <div style="padding: 14px; border-radius: 10px; background: #fee2e2; color: #991b1b; font-weight: 600;">
            Kode invoice tidak ditemukan. Periksa kembali kode invoice pada nota Anda.
        </div>
    @endif

    @if($invoice)
        @php
            $order = $invoice->laundryOrder;
            $customer = $order?->customer?->user;
            $service = $order?->service;
            $statusMap = [
                'diterima' => 'Diterima',
                'dicuci' => 'Sedang Dicuci',
                'dijemur' => 'Sedang Dijemur',
                'disetrika' => 'Sedang Disetrika',
                'siap_diambil' => 'Siap Diambil',
                'selesai' => 'Selesai',
                'dibatalkan' => 'Dibatalkan',
            ];
            $statusCucian = $statusMap[$order->status] ?? ucwords(str_replace('_', ' ', $order->status));
            $statusPembayaran = $invoice->status === 'paid' ? 'Lunas' : 'Belum Lunas';
            $estimatedDate = $order?->created_at && $service
                ? $order->created_at->copy()->addHours($service->estimated_hours ?? 48)->format('d M Y')
                : '-';
        @endphp

        <div style="margin-top: 18px; padding: 18px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: left;">
            <h3 style="margin-bottom: 12px; color: #0f172a;">Hasil Pencarian</h3>

            <div style="display: grid; grid-template-columns: 180px 1fr; gap: 8px; font-size: 14px; color: #334155;">
                <span>Invoice</span><strong>{{ $invoice->invoice_code }}</strong>
                <span>Pelanggan</span><strong>{{ $customer->name ?? '-' }}</strong>
                <span>Layanan</span><strong>{{ $service->name ?? '-' }}</strong>
                <span>Status Cucian</span><strong>{{ $statusCucian }}</strong>
                <span>Status Pembayaran</span><strong>{{ $statusPembayaran }}</strong>
                <span>Total Tagihan</span><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                <span>Estimasi Selesai</span><strong>{{ $estimatedDate }}</strong>
            </div>
        </div>

        <div style="margin-top: 18px; padding: 18px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; text-align: left;">
            <h3 style="margin-bottom: 12px; color: #0f172a;">Riwayat Status</h3>

            @forelse($order->statusHistories as $history)
                <div style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #334155;">
                    <strong>{{ $statusMap[$history->status] ?? ucwords(str_replace('_', ' ', $history->status)) }}</strong><br>
                    <span>{{ $history->created_at->format('d M Y H:i') }}</span><br>
                    <small>{{ $history->note ?? '-' }}</small>
                </div>
            @empty
                <p style="color: #64748b; font-size: 14px;">Belum ada riwayat status.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection
