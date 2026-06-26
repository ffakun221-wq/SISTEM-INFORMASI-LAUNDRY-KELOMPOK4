@extends('layouts.app')

@section('content')
<section class="page-header payment-page-header">
    <h1>Pembayaran</h1>
    <p>Kelola pembayaran, invoice, dan cetak nota pelanggan</p>
</section>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="customer-toolbar" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('payments.index') }}" style="display: flex; gap: 8px;">
        <div style="position: relative; flex: 1; max-width: 400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode INV, ID Pesanan, atau Nama..." style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; outline: none;">
        </div>
        <button type="submit" style="height: 42px; background: #0f172a; color: white; border: none; border-radius: 8px; padding: 0 20px; font-weight: 600; cursor: pointer;">Cari</button>
        @if(request('search'))
            <a href="{{ route('payments.index') }}" style="height: 42px; display: inline-flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 16px; color: #64748b; text-decoration: none; font-weight: 600; background: #f8fafc;">Reset</a>
        @endif
    </form>
</div>

<div class="payment-table-card">
    <table class="payment-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Nama Pelanggan</th>
                <th>Layanan</th>
                <th>Total Tagihan</th>
                <th>Status Pembayaran</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($invoices as $invoice)
                @php
                    $order = $invoice->laundryOrder;
                    $customer = $order?->customer;
                    $user = $customer?->user;
                    $service = $order?->service;
                    $payment = $invoice->payment;
                    $statusLabel = $invoice->status === 'paid' ? 'Lunas' : 'Belum Lunas';
                    $methodLabel = $payment?->method ? strtoupper($payment->method) : '-';
                    $receiptDate = $payment?->paid_at
                        ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y H:i')
                        : \Carbon\Carbon::parse($invoice->issued_at ?? $invoice->created_at)->format('d M Y H:i');
                @endphp

                <tr>
                    <td>
                        <strong>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</strong><br>
                        <small style="color: #94a3b8; font-size: 11px;">{{ $invoice->invoice_code }}</small>
                    </td>
                    <td>{{ $user->name ?? '-' }}</td>
                    <td>{{ $service->name ?? '-' }}</td>
                    <td><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
                    <td>
                        <span class="payment-status {{ $invoice->status === 'paid' ? 'paid' : 'unpaid' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if($invoice->status !== 'paid')
                            <button
                                type="button"
                                class="payment-detail-btn open-payment-modal"
                                data-action="{{ route('payments.process', $invoice) }}"
                                data-invoice="{{ $invoice->invoice_code }}"
                                data-customer="{{ $user->name ?? '-' }}"
                                data-service="{{ $service->name ?? '-' }}"
                                data-total="{{ $invoice->total_amount }}"
                            >
                                Bayar
                            </button>
                        @endif

                        <button
                            type="button"
                            class="payment-detail-btn open-receipt-modal"
                            style="background: {{ $invoice->status === 'paid' ? '#10b981' : '#64748b' }}; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;"
                            data-invoice="{{ $invoice->invoice_code }}"
                            data-customer="{{ $user->name ?? '-' }}"
                            data-service="{{ $service->name ?? '-' }}"
                            data-total="{{ $invoice->total_amount }}"
                            data-status="{{ $statusLabel }}"
                            data-method="{{ $methodLabel }}"
                            data-date="{{ $receiptDate }}"
                        >
                            🖨️ Cetak Nota
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">
                        Belum ada invoice pembayaran.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- MODAL PEMBAYARAN --}}
<div class="modal-overlay" id="paymentModal">
    <div class="payment-modal-card">
        <div class="payment-modal-header">
            <h3>Konfirmasi Pembayaran</h3>
            <button type="button" class="modal-close-btn" data-close-payment-modal>&times;</button>
        </div>

        <form method="POST" action="#" id="paymentForm">
            @csrf

            <div class="payment-detail-row">
                <span>Invoice</span>
                <strong id="modalInvoiceCode">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Pelanggan</span>
                <strong id="modalCustomerName">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Layanan</span>
                <strong id="modalServiceName">-</strong>
            </div>

            <div class="payment-form-group">
                <label>Metode Pembayaran</label>
                <select name="method" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="cash">Cash</option>
                    <option value="qris">QRIS</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>

            <div class="payment-service-box">
                <div class="payment-detail-row payment-total-row">
                    <span>Total Tagihan</span>
                    <strong id="modalTotal">Rp 0</strong>
                </div>
            </div>

            <div class="payment-modal-actions">
                <button type="button" class="modal-cancel-btn" data-close-payment-modal>Tutup</button>
                <button type="submit" class="modal-submit-btn">Proses Pembayaran</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL NOTA --}}
<div class="modal-overlay" id="receiptModal">
    <div class="payment-success-card" style="position: relative;">
        <button
            type="button"
            class="modal-close-btn"
            data-close-receipt-modal
            style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 24px; cursor: pointer;"
        >
            &times;
        </button>

        <h2 style="margin-top: 10px;">Nota Laundry</h2>
        <p>Nota ini dapat digunakan sebagai bukti order.</p>

        <div class="receipt-box receipt-print-area">
            <div class="payment-detail-row">
                <span>Invoice</span>
                <strong id="reprintInvoiceCode">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Pelanggan</span>
                <strong id="reprintCustomerName">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Layanan</span>
                <strong id="reprintService">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Status Pembayaran</span>
                <strong id="reprintStatus" style="color: #0ea5e9;">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Metode Pembayaran</span>
                <strong id="reprintMethod">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Total Tagihan</span>
                <strong id="reprintTotal">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Tanggal Nota</span>
                <strong id="reprintDate">-</strong>
            </div>
        </div>

        <div class="payment-modal-actions">
            <button type="button" class="modal-cancel-btn" data-close-receipt-modal>Tutup</button>
            <button type="button" class="modal-submit-btn" data-print-receipt>🖨️ Cetak Nota</button>
        </div>
    </div>
</div>

{{-- MODAL PEMBAYARAN BERHASIL --}}
@if($paidInvoice)
    @php
        $paidOrder = $paidInvoice->laundryOrder;
        $paidCustomer = $paidOrder?->customer?->user;
        $paidService = $paidOrder?->service;
        $paidPayment = $paidInvoice->payment;
    @endphp

    <div class="modal-overlay show" id="successPaymentModal">
        <div class="payment-success-card" style="position: relative;">
            <a
                href="{{ route('payments.index') }}"
                class="modal-close-btn"
                style="position: absolute; right: 20px; top: 20px; text-decoration: none; color: #94a3b8; font-size: 24px;"
            >
                &times;
            </a>

            <h2>Pembayaran Berhasil!</h2>
            <p>Pembayaran untuk invoice {{ $paidInvoice->invoice_code }} telah dikonfirmasi.</p>

            <div class="receipt-box receipt-print-area" id="receiptArea">
                <h3>Nota Laundry</h3>

                <div class="payment-detail-row">
                    <span>Invoice</span>
                    <strong>{{ $paidInvoice->invoice_code }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Pelanggan</span>
                    <strong>{{ $paidCustomer->name ?? '-' }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Layanan</span>
                    <strong>{{ $paidService->name ?? '-' }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Status Pembayaran</span>
                    <strong>Lunas</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Metode Pembayaran</span>
                    <strong>{{ strtoupper($paidPayment->method ?? '-') }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Total Dibayar</span>
                    <strong>Rp {{ number_format($paidPayment->amount_paid ?? $paidInvoice->total_amount, 0, ',', '.') }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Tanggal</span>
                    <strong>{{ $paidPayment?->paid_at ? \Carbon\Carbon::parse($paidPayment->paid_at)->format('d M Y H:i') : $paidInvoice->updated_at->format('d M Y H:i') }}</strong>
                </div>
            </div>

            <div class="payment-modal-actions">
                <a href="{{ route('payments.index') }}" class="modal-cancel-btn success-link">Selesai</a>
                <button type="button" class="modal-submit-btn" data-print-receipt>🖨️ Cetak Nota</button>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentModal = document.getElementById('paymentModal');
    const paymentForm = document.getElementById('paymentForm');
    const openPaymentButtons = document.querySelectorAll('.open-payment-modal');
    const closePaymentButtons = document.querySelectorAll('[data-close-payment-modal]');

    const modalInvoice = document.getElementById('modalInvoiceCode');
    const modalCustomer = document.getElementById('modalCustomerName');
    const modalService = document.getElementById('modalServiceName');
    const modalTotal = document.getElementById('modalTotal');

    const receiptModal = document.getElementById('receiptModal');
    const openReceiptButtons = document.querySelectorAll('.open-receipt-modal');
    const closeReceiptButtons = document.querySelectorAll('[data-close-receipt-modal]');

    const reprintInvoice = document.getElementById('reprintInvoiceCode');
    const reprintCustomer = document.getElementById('reprintCustomerName');
    const reprintService = document.getElementById('reprintService');
    const reprintStatus = document.getElementById('reprintStatus');
    const reprintMethod = document.getElementById('reprintMethod');
    const reprintTotal = document.getElementById('reprintTotal');
    const reprintDate = document.getElementById('reprintDate');

    function rupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(number || 0));
    }

    function openModal(modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    openPaymentButtons.forEach(button => {
        button.addEventListener('click', function () {
            paymentForm.action = this.dataset.action;
            modalInvoice.innerText = this.dataset.invoice;
            modalCustomer.innerText = this.dataset.customer;
            modalService.innerText = this.dataset.service;
            modalTotal.innerText = rupiah(this.dataset.total);
            openModal(paymentModal);
        });
    });

    closePaymentButtons.forEach(button => {
        button.addEventListener('click', () => closeModal(paymentModal));
    });

    paymentModal.addEventListener('click', function (event) {
        if (event.target === paymentModal) closeModal(paymentModal);
    });

    openReceiptButtons.forEach(button => {
        button.addEventListener('click', function () {
            reprintInvoice.innerText = this.dataset.invoice;
            reprintCustomer.innerText = this.dataset.customer;
            reprintService.innerText = this.dataset.service;
            reprintStatus.innerText = this.dataset.status;
            reprintMethod.innerText = this.dataset.method;
            reprintTotal.innerText = rupiah(this.dataset.total);
            reprintDate.innerText = this.dataset.date;
            openModal(receiptModal);
        });
    });

    closeReceiptButtons.forEach(button => {
        button.addEventListener('click', () => closeModal(receiptModal));
    });

    receiptModal.addEventListener('click', function (event) {
        if (event.target === receiptModal) closeModal(receiptModal);
    });


    function printReceiptOnly(receipt) {
        if (!receipt) {
            alert('Area nota tidak ditemukan.');
            return;
        }

        const printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        printFrame.setAttribute('aria-hidden', 'true');

        document.body.appendChild(printFrame);

        const printDocument = printFrame.contentWindow.document;
        printDocument.open();
        printDocument.write(`
            <!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Nota Laundry</title>
                <style>
                    @page {
                        size: A4;
                        margin: 12mm;
                    }

                    * {
                        box-sizing: border-box;
                    }

                    body {
                        margin: 0;
                        padding: 0;
                        background: #ffffff;
                        color: #0f172a;
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                    }

                    .receipt-box {
                        width: 92mm;
                        max-width: 100%;
                        margin: 0 auto;
                        padding: 0;
                        border: 0;
                        box-shadow: none;
                        background: #ffffff;
                    }

                    .receipt-box h3 {
                        margin: 0 0 14px;
                        text-align: center;
                        font-size: 16px;
                    }

                    .payment-detail-row {
                        display: flex;
                        justify-content: space-between;
                        gap: 14px;
                        padding: 7px 0;
                        border-bottom: 1px solid #e2e8f0;
                    }

                    .payment-detail-row span {
                        color: #334155;
                    }

                    .payment-detail-row strong {
                        color: #0f172a;
                        text-align: right;
                    }
                </style>
            </head>
            <body>
                ${receipt.outerHTML}
            </body>
            </html>
        `);
        printDocument.close();

        setTimeout(() => {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();

            setTimeout(() => {
                document.body.removeChild(printFrame);
            }, 1000);
        }, 250);
    }

    document.querySelectorAll('[data-print-receipt]').forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('.payment-success-card');
            const receipt = card ? card.querySelector('.receipt-print-area') : null;
            printReceiptOnly(receipt);
        });
    });
});
</script>
@endsection
