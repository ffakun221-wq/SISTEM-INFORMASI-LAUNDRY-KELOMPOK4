@extends('layouts.app')

@section('content')
<section class="page-header transaction-page-header">
    <h1>Log Notifikasi WhatsApp</h1>
    <p>Pantau notifikasi WhatsApp yang terkirim saat order siap diambil</p>
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
    <form method="GET" action="{{ route('logwa.index') }}" class="customer-search-form">
        <div class="customer-search-box">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari transaksi (nama, no order)..."
            >
        </div>
        <button type="submit" class="customer-search-cbtn">
            Cari
        </button>
        @if(request('search'))
            <a href="{{ route('logwa.index') }}" class="customer-search-cbtn-reset">
                Reset
            </a>
        @endif
    </form>
</div>

<div class="transaction-table-card">
    <table class="transaction-table">
        <thead>
            <tr>
                <th>ID. Order</th>
                <th>Pelanggan</th>
                <th>Channel</th>
                <th>No. Penerima</th>
                <th>Status</th>
                <th>Alasan Error</th>
                <th>Dibuat Pada</th>
                <th>Pesan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td><strong>ORD-{{ str_pad($log->laundry_order_id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $log->customer->user->name ?? '-' }}</td>
                    <td>{{ $log->channel ?? '-' }}</td>
                    <td>{{ $log->recipient }}</td>
                    <td><strong>{{ $log->status}}</strong></td>
                    <td>{{ $log->error_message }}</td>
                    <td>{{ $log->created_at }}</td>
                    <td>
                        <button
                            type="button"
                            class="edit-action open-msg-modal"
                            title="Edit"
                            data-message="{{ $log->message }}"
                        >👁</button>
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

{{-- MODAL OVERLAY MESSAGE --}}
<div class="modal-overlay" id="logmsgmodal">
    <div class="customer-modal-card" style="max-height: 90vh; overflow-y: auto;">
        <div class="customer-modal-header">
            <h3>Message</h3>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>
        <div class="modal-form-group">
            <textarea name="msg" id="msgarea" readonly></textarea>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const msgmodal = document.getElementById('logmsgmodal');
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    const msgbuttons = document.querySelectorAll('.open-msg-modal');

    const msgarea = document.getElementById('msgarea');

    function openModal(modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    msgbuttons.forEach(button => {
        button.addEventListener('click', function () {
            const message = this.dataset.message;

            msgarea.value = message;

            openModal(msgmodal);
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', function () {
            closeModal(msgmodal);
        });
    });

    msgmodal.addEventListener('click', function (event) {
        if (event.target === msgmodal) {
            closeModal(msgmodal);
        }
    });
});
</script>
@endsection
