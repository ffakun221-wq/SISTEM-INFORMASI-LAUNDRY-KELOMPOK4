@extends('layouts.app')

@section('content')
<section class="page-header logwa-page-header">
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
                placeholder="Cari notifikasi (nama pelanggan, no order)..."
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

<div style="display: block;">
    <div class="logwa-table-card">
        <table class="logwa-table">
            <thead>
                <tr>
                    <th>No. Order</th>
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
                    @php
                        $statusLabel = match($log->status) {
                            'pending' => 'Pending',
                            'sent' => 'Berhasil',
                            'failed' => 'Gagal',
                            default => $log->status,
                        };

                        $statusClass = match($log->status) {
                            'pending' => 'logwa-pending',
                            'sent' => 'logwa-sent',
                            'failed' => 'logwa-failed',
                            default => 'pending',
                        };
                    @endphp
                    <tr>
                        <td><strong>ORD-{{ str_pad($log->laundry_order_id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $log->customer->user->name ?? '-' }}</td>
                        <td>{{ $log->channel ?? '-' }}</td>
                        <td><strong>{{ $log->recipient }}</strong></td>
                        <td><span class="logwa-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td>{{ $log->error_message }}</td>
                        <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="logwa-msgview-td">
                            <button
                                type="button"
                                class="logwa-msgview open-msg-modal"
                                title="Lihat Pesan"
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

    <div style="margin-top: 16px;">
        {{ $logs->links('pagination::bootstrap-4') }}
    </div>
</div>

{{-- MODAL OVERLAY MESSAGE --}}
<div class="logwa-modal-overlay" id="logmsgmodal">
    <div class="logwa-modal-card" style="max-height: 90vh; overflow-y: auto;">
        <div class="logwa-modal-header">
            <h3>Pesan Notifikasi</h3>
            <button type="button" class="logwa-modal-close-btn" data-close-modal>&times;</button>
        </div>
        <div class="logwa-modal-form-group">
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
<style>
    .pagination {
        display: flex;
        padding-left: 0;
        list-style: none;
        gap: 6px;
        justify-content: flex-end; /* Posisi di kanan */
        margin-top: 10px;
        margin-bottom: 0;
    }

    .page-item .page-link {
        position: relative;
        display: block;
        padding: 8px 14px;
        color: #64748b;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px; /* Membuat sudut membulat */
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }

    /* Warna tombol saat kursor diarahkan (hover) */
    .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #f8fafc;
        color: #0ea5e9;
        border-color: #bae6fd;
    }

    /* Warna tombol untuk halaman yang sedang aktif */
    .page-item.active .page-link {
        z-index: 3;
        color: #ffffff;
        background-color: #0ea5e9;
        border-color: #0ea5e9;
        box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);
    }

    /* Tampilan tombol yang tidak bisa diklik (misal tombol '<' di halaman pertama) */
    .page-item.disabled .page-link {
        color: #94a3b8;
        pointer-events: none;
        background-color: #f1f5f9;
        border-color: #e2e8f0;
    }
</style>
@endsection
