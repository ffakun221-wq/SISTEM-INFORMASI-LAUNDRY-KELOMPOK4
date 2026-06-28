@extends('layouts.app')

@section('content')
<section class="page-header payment-page-header">
    <h1>Pembayaran</h1>
    <p>Kelola pembayaran dan invoice pelanggan</p>
</section>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<div class="customer-toolbar">
    <form method="GET" action="{{ route('payments.index') }}" class="customer-search-form">
        <div class="customer-search-box">
            {{-- <span class="search-icon">⌕</span> --}}
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari pembayaran (kode INV, no order, nama pelanggan)..."
            >
        </div>
        <button type="submit" class="customer-search-cbtn">
            Cari
        </button>
        @if(request('search'))
            <a href="{{ route('payments.index') }}" class="customer-search-cbtn-reset">
                Reset
            </a>
        @endif
    </form>
</div>

<div class="payment-table-card">
    <table class="payment-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Nama Pelanggan</th>
                <th>Total Tagihan</th>
                <th>Tanggal Jatuh Tempo</th>
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

                    $dueDate = \Carbon\Carbon::parse($invoice->issued_at ?? $invoice->created_at)
                        ->addDays(3)
                        ->format('d M Y');

                    $baseTotal = ($invoice->subtotal ?? 0) + ($invoice->delivery_fee ?? 0);
                    $statusLabel = $invoice->status === 'paid' ? 'Lunas' : 'Belum Lunas';
                @endphp

                <tr>
                    <td>
                        <strong>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</strong><br>
                        <small style="color: #94a3b8; font-size: 11px;">{{ $invoice->invoice_code }}</small>
                    </td>
                    <td>{{ $user->name ?? '-' }}</td>
                    <td><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
                    <td>{{ $dueDate }}</td>
                    <td>
                        <span class="payment-status {{ $invoice->status === 'paid' ? 'paid' : 'unpaid' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        @if($invoice->status === 'paid')
                            <button
                                type="button"
                                class="payment-detail-btn open-receipt-modal"
                                style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;"
                                data-invoice="{{ $invoice->invoice_code }}"
                                data-customer="{{ $user->name ?? '-' }}"
                                data-total="{{ $invoice->total_amount }}"
                                data-date="{{ $invoice->updated_at->format('d M Y H:i') }}"
                                data-method="{{ strtoupper(optional($invoice->payment)->method ?? '-') }}"
                            >
                                📄 Lihat Nota
                            </button>
                        @else
                            <button
                                type="button"
                                class="payment-detail-btn open-payment-modal"
                                data-id="{{ $invoice->id }}"
                                data-invoice="{{ $invoice->invoice_code }}"
                                data-customer="{{ $user->name ?? '-' }}"
                                data-service="{{ $service->name ?? '-' }}"
                                data-weight="{{ $order->weight ?? 0 }}"
                                data-quantity="{{ $order->quantity ?? 0 }}"
                                data-subtotal="{{ $invoice->subtotal }}"
                                data-delivery="{{ $invoice->delivery_fee }}"
                                data-total="{{ $baseTotal }}"
                                data-points="{{ $customer->points_balance ?? 0 }}"
                                data-action="{{ route('payments.process', $invoice) }}"
                            >
                                Rincian & Bayar
                            </button>
                        @endif
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

{{-- MODAL RINCIAN PEMBAYARAN (Belum Lunas) --}}
<div class="modal-overlay" id="paymentModal">
    <div class="payment-modal-card">
        <div class="payment-modal-header">
            <h3>Rincian Invoice</h3>
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

            <div class="payment-form-group">
                <label>Metode Pembayaran</label>
                <select name="method" id="paymentMethod" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="cash">Cash</option>
                    <option value="qris">QRIS</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>

            <div class="payment-form-group">
                <label>Poin Diskon</label>
                <input
                    type="number"
                    name="points_used"
                    id="pointsUsed"
                    min="0"
                    value="0"
                    placeholder="Masukkan poin"
                >
                <small id="availablePointsText">Poin tersedia: 0</small>
            </div>

            <div class="payment-service-box">
                <h4>Detail Layanan</h4>

                <div class="payment-detail-row">
                    <span id="modalServiceName">-</span>
                    <strong id="modalSubtotal">Rp 0</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Biaya Antar</span>
                    <strong id="modalDeliveryFee">Rp 0</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Diskon Poin</span>
                    <strong id="modalPointDiscount">Rp 0</strong>
                </div>

                <div class="payment-detail-row payment-total-row">
                    <span>Total</span>
                    <strong id="modalTotal">Rp 0</strong>
                </div>
            </div>

            <div class="payment-modal-actions">
                <button type="button" class="modal-cancel-btn" data-close-payment-modal>Tutup</button>
                <button type="submit" class="modal-submit-btn" id="processPaymentBtn">
                    Proses Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL LIHAT NOTA (Cetak Ulang) --}}
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
        <h2 style="margin-top: 10px;">Nota Pembayaran</h2>
        <p>Salinan nota untuk transaksi yang sudah lunas.</p>

        <div class="receipt-box">
            <div class="payment-detail-row">
                <span>Invoice</span>
                <strong id="reprintInvoiceCode">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Pelanggan</span>
                <strong id="reprintCustomerName">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Metode Pembayaran</span>
                <strong id="reprintMethod" style="color: #0ea5e9;">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Total Dibayar</span>
                <strong id="reprintTotal">-</strong>
            </div>

            <div class="payment-detail-row">
                <span>Waktu Bayar</span>
                <strong id="reprintDate">-</strong>
            </div>
        </div>

        <div class="payment-modal-actions">
            <button type="button" class="modal-cancel-btn" data-close-receipt-modal>Tutup</button>
            <button type="button" class="modal-submit-btn" onclick="window.print()">🖨️ Cetak Nota</button>
        </div>
    </div>
</div>

{{-- MODAL PEMBAYARAN BERHASIL (Muncul Sekali Pasca Bayar) --}}
@if($paidInvoice)
    @php
        $paidOrder = $paidInvoice->laundryOrder;
        $paidCustomer = $paidOrder?->customer?->user;
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

            <div class="receipt-box" id="receiptArea">
                <h3>Nota Pembayaran</h3>

                <div class="payment-detail-row">
                    <span>Invoice</span>
                    <strong>{{ $paidInvoice->invoice_code }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Pelanggan</span>
                    <strong>{{ $paidCustomer->name ?? '-' }}</strong>
                </div>

                <div class="payment-detail-row">
                    <span>Metode Pembayaran</span>
                    <strong>{{ strtoupper($paidPayment->method ?? $paidPayment->payment_method ?? '-') }}</strong>
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
                <button type="button" class="modal-submit-btn" onclick="window.print()">🖨️ Cetak Nota</button>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    // VARIABEL MODAL PEMBAYARAN UTAMA
    const modal = document.getElementById('paymentModal');
    const openButtons = document.querySelectorAll('.open-payment-modal');
    const closeButtons = document.querySelectorAll('[data-close-payment-modal]');

    const paymentForm = document.getElementById('paymentForm');
    const processPaymentBtn = document.getElementById('processPaymentBtn');

    const invoiceCode = document.getElementById('modalInvoiceCode');
    const customerName = document.getElementById('modalCustomerName');
    const serviceName = document.getElementById('modalServiceName');
    const subtotalText = document.getElementById('modalSubtotal');
    const deliveryText = document.getElementById('modalDeliveryFee');
    const pointDiscountText = document.getElementById('modalPointDiscount');
    const totalText = document.getElementById('modalTotal');
    const pointsInput = document.getElementById('pointsUsed');
    const availablePointsText = document.getElementById('availablePointsText');

    // VARIABEL MODAL LIHAT NOTA
    const receiptModal = document.getElementById('receiptModal');
    const openReceiptButtons = document.querySelectorAll('.open-receipt-modal');
    const closeReceiptButtons = document.querySelectorAll('[data-close-receipt-modal]');

    const reprintInvoice = document.getElementById('reprintInvoiceCode');
    const reprintCustomer = document.getElementById('reprintCustomerName');
    const reprintMethod = document.getElementById('reprintMethod'); // BARIS BARU
    const reprintTotal = document.getElementById('reprintTotal');
    const reprintDate = document.getElementById('reprintDate');

    let currentBaseTotal = 0;
    let currentAvailablePoints = 0;
    const pointValue = {{ $point }};

    function rupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(number);
    }

    // FUNGSI MODAL PEMBAYARAN
    function openModal() {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    function updateTotal() {
        const pointsUsed = Math.min(
            Number(pointsInput.value || 0),
            currentAvailablePoints
        );

        const discount = pointsUsed * pointValue;
        const finalTotal = Math.max(0, currentBaseTotal - discount);

        pointDiscountText.innerText = rupiah(discount);
        totalText.innerText = rupiah(finalTotal);
    }

    openButtons.forEach(button => {
        button.addEventListener('click', function () {
            paymentForm.action = this.dataset.action;

            invoiceCode.innerText = this.dataset.invoice;
            customerName.innerText = this.dataset.customer;
            serviceName.innerText = this.dataset.service;

            const subtotal = Number(this.dataset.subtotal || 0);
            const delivery = Number(this.dataset.delivery || 0);
            currentBaseTotal = Number(this.dataset.total || 0);
            currentAvailablePoints = Number(this.dataset.points || 0);

            subtotalText.innerText = rupiah(subtotal);
            deliveryText.innerText = rupiah(delivery);
            pointsInput.value = 0;
            pointsInput.max = currentAvailablePoints;
            availablePointsText.innerText = `Poin tersedia: ${currentAvailablePoints}`;

            updateTotal();
            openModal();
        });
    });

    pointsInput.addEventListener('input', updateTotal);

    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // FUNGSI MODAL LIHAT NOTA
    function openReceipt() {
        receiptModal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeReceipt() {
        receiptModal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    openReceiptButtons.forEach(button => {
        button.addEventListener('click', function () {
            reprintInvoice.innerText = this.dataset.invoice;
            reprintCustomer.innerText = this.dataset.customer;
            reprintMethod.innerText = this.dataset.method; // SET METODE DI SINI
            reprintTotal.innerText = rupiah(Number(this.dataset.total || 0));
            reprintDate.innerText = this.dataset.date;
            openReceipt();
        });
    });

    closeReceiptButtons.forEach(button => {
        button.addEventListener('click', closeReceipt);
    });

    receiptModal.addEventListener('click', function (event) {
        if (event.target === receiptModal) {
            closeReceipt();
        }
    });
});
</script>
@endsection
