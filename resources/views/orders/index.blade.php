@extends('layouts.app')

@section('content')
<section class="page-header transaction-page-header">
    <h1>Manajemen Transaksi</h1>
    <p>Kelola transaksi dan order laundry</p>
</section>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="customer-toolbar" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('orders.index') }}" style="display: flex; gap: 8px;">
        <div style="position: relative; flex: 1; max-width: 400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Pelanggan atau ID Pesanan..." style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; outline: none;">
        </div>
        <button type="submit" style="height: 42px; background: #0f172a; color: white; border: none; border-radius: 8px; padding: 0 20px; font-weight: 600; cursor: pointer;">Cari</button>
        @if(request('search'))
            <a href="{{ route('orders.index') }}" style="height: 42px; display: inline-flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 16px; color: #64748b; text-decoration: none; font-weight: 600; background: #f8fafc;">Reset</a>
        @endif
    </form>
</div>

<div class="transaction-toolbar">
    <button type="button" class="transaction-add-btn" id="openCreateOrderModal">
        <span>+</span>
        Buat Transaksi Baru
    </button>
</div>

<div class="transaction-table-card">
    <table class="transaction-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Pelanggan</th>
                <th>Layanan</th>
                <th>Jumlah</th>
                <th>Total Harga</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($orders as $order)
                @php
                    $isKiloan = ($order->service->type ?? 'kiloan') === 'kiloan';
                    $jumlah = $isKiloan
                        ? rtrim(rtrim(number_format($order->weight ?? 0, 1, ',', '.'), '0'), ',') . ' kg'
                        : ($order->quantity ?? 0) . ' item';

                    $statusLabel = $order->status === 'selesai' ? 'Selesai' : 'Proses';
                    $statusClass = $order->status === 'selesai' ? 'status-done' : 'status-process';
                @endphp

                <tr>
                    <td><strong>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $order->customer->user->name ?? '-' }}</td>
                    <td>{{ $order->service->name ?? '-' }}</td>
                    <td>{{ $jumlah }}</td>
                    <td><strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></td>
                    <td>{{ $order->created_at->format('d M Y') }}</td>
                    <td>
                        <span class="transaction-status {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="transaction-eye-action" title="Detail">
                            👁
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-row">
                        Belum ada transaksi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- MODAL BUAT TRANSAKSI --}}
<div class="modal-overlay" id="createOrderModal">
    <div class="transaction-modal-card">
        <div class="transaction-modal-header">
            <h3>Buat Transaksi Baru</h3>
            <button type="button" class="modal-close-btn" data-close-order-modal>&times;</button>
        </div>

        <form method="POST" action="{{ route('orders.store') }}" class="transaction-modal-form">
            @csrf


            <input type="hidden" name="weight" id="order_weight">
            <input type="hidden" name="quantity" id="order_quantity">

            <div class="transaction-modal-group">
                <label>Pilih Pelanggan</label>
                <select name="customer_id" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->user->name }} - {{ $customer->phone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="transaction-modal-group">
                <label>Pilih Layanan</label>
                <select name="service_id" id="order_service_id" required>
                    <option value="">-- Pilih Layanan --</option>
                    @foreach($services as $service)
                        <option
                            value="{{ $service->id }}"
                            data-type="{{ $service->type }}"
                            data-price="{{ $service->price }}"
                            data-hours="{{ $service->estimated_hours }}"
                        >
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="transaction-modal-row">
                <div class="transaction-modal-group">
                    <label>Berat (kg) / Jumlah</label>
                    <input
                        type="number"
                        step="0.1"
                        min="0"
                        id="order_amount"
                        placeholder="Masukkan jumlah"
                        required
                    >
                </div>

                <div class="transaction-modal-group">
                    <label>Estimasi Selesai</label>
                    <input
                        type="date"
                        id="estimated_finish_date"
                        readonly
                    >
                </div>
            </div>

            <div class="transaction-total-preview">
                <span>Estimasi Total</span>
                <strong id="orderTotalPreview">Rp 0</strong>
            </div>

            <div class="transaction-modal-actions">
                <button type="button" class="modal-cancel-btn" data-close-order-modal>Batal</button>
                <button type="submit" class="modal-submit-btn">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('createOrderModal');
    const openBtn = document.getElementById('openCreateOrderModal');
    const closeButtons = document.querySelectorAll('[data-close-order-modal]');

    const serviceSelect = document.getElementById('order_service_id');
    const amountInput = document.getElementById('order_amount');
    const weightInput = document.getElementById('order_weight');
    const quantityInput = document.getElementById('order_quantity');
    const estimatedDateInput = document.getElementById('estimated_finish_date');
    const totalPreview = document.getElementById('orderTotalPreview');

    function rupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(number);
    }

    function openModal() {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    function updateTransactionPreview() {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const serviceType = selectedOption?.dataset?.type || '';
        const price = Number(selectedOption?.dataset?.price || 0);
        const hours = Number(selectedOption?.dataset?.hours || 24);
        const amount = Number(amountInput.value || 0);

        if (serviceType === 'kiloan') {
            weightInput.value = amount;
            quantityInput.value = '';
        } else if (serviceType === 'satuan') {
            weightInput.value = '';
            quantityInput.value = Math.floor(amount);
        } else {
            weightInput.value = '';
            quantityInput.value = '';
        }

        totalPreview.innerText = rupiah(price * amount);

        if (serviceSelect.value) {
            const finishDate = new Date();
            finishDate.setHours(finishDate.getHours() + hours);

            const year = finishDate.getFullYear();
            const month = String(finishDate.getMonth() + 1).padStart(2, '0');
            const day = String(finishDate.getDate()).padStart(2, '0');

            estimatedDateInput.value = `${year}-${month}-${day}`;
        } else {
            estimatedDateInput.value = '';
        }
    }

    openBtn.addEventListener('click', openModal);

    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    serviceSelect.addEventListener('change', updateTransactionPreview);
    amountInput.addEventListener('input', updateTransactionPreview);

    @if($errors->any())
        openModal();
    @endif
});
</script>
@endsection
