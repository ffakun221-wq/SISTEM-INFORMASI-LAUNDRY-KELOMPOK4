@extends('layouts.app')

@section('content')
<section class="page-header">
    <h1>Buat Transaksi Baru</h1>
    <p>Tambahkan order laundry baru</p>
</section>

<div class="form-card">
    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label>Pelanggan</label>
                <select name="customer_id" class="form-control">
                    <option value="">Pilih pelanggan</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->user->name }} - {{ $customer->phone }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <small class="error-text">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label>Layanan</label>
                <select name="service_id" id="service_id" class="form-control">
                    <option value="">Pilih layanan</option>
                    @foreach($services as $service)
                        <option
                            value="{{ $service->id }}"
                            data-price="{{ $service->price }}"
                            data-type="{{ $service->type }}"
                            {{ old('service_id') == $service->id ? 'selected' : '' }}
                        >
                            {{ $service->name }} - Rp {{ number_format($service->price, 0, ',', '.') }} / {{ $service->type }}
                        </option>
                    @endforeach
                </select>
                @error('service_id') <small class="error-text">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Berat Cucian (kg)</label>
                <input type="number" step="0.1" name="weight" id="weight" class="form-control" placeholder="Contoh: 3.5" value="{{ old('weight') }}">
                @error('weight') <small class="error-text">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label>Jumlah Satuan</label>
                <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Contoh: 2" value="{{ old('quantity') }}">
                @error('quantity') <small class="error-text">{{ $message }}</small> @enderror
            </div>
        </div>


        <div class="form-row">
            <div class="form-group">
                <label>Sumber Order</label>
                <input type="text" class="form-control" value="Outlet" disabled>
            </div>

            <div class="form-group">
                <label>Opsi Pengambilan</label>
                <input type="text" class="form-control" value="Ambil Sendiri" disabled>
            </div>
        </div>

        <div class="summary-box">
            <p>Estimasi Total</p>
            <h2 id="totalPreview">Rp 0</h2>
        </div>

        <div class="form-actions">
            <a href="{{ route('orders.index') }}" class="btn-cancel">Batal</a>
            <button type="submit" class="btn-internal-primary">Simpan Transaksi</button>
        </div>
    </form>
</div>

<script>
    const serviceSelect = document.getElementById('service_id');
    const weightInput = document.getElementById('weight');
    const quantityInput = document.getElementById('quantity');
    const totalPreview = document.getElementById('totalPreview');

    function rupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(number);
    }

    function calculateTotal() {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const price = Number(selectedOption?.dataset?.price || 0);
        const type = selectedOption?.dataset?.type || 'kiloan';

        const weight = Number(weightInput.value || 0);
        const quantity = Number(quantityInput.value || 0);
        const base = type === 'kiloan' ? weight : quantity;
        const total = base * price;

        totalPreview.innerText = rupiah(total);
    }

    serviceSelect.addEventListener('change', calculateTotal);
    weightInput.addEventListener('input', calculateTotal);
    quantityInput.addEventListener('input', calculateTotal);
</script>
@endsection
