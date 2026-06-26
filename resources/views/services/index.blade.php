@extends('layouts.app')

@section('content')
<section class="page-header">
    <h1>Manajemen Layanan</h1>
    <p>Kelola daftar layanan laundry, harga, dan estimasi waktu pengerjaan.</p>
</section>

@if(session('success'))
    <div class="alert-success" style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #dcfce7; color: #166534;">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert-error" style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #fee2e2; color: #991b1b;">{{ session('error') }}</div>
@endif

<div class="customer-toolbar" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
    <form method="GET" action="{{ route('services.index') }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari layanan..." style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; outline: none;">
        <button type="submit" style="height: 40px; background: #0f172a; color: white; border: none; border-radius: 8px; padding: 0 16px; cursor: pointer;">Cari</button>
    </form>
    <button type="button" id="openCreateModalBtn" style="background: #0ea5e9; color: white; border: none; padding: 15px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
        + Tambah Layanan
    </button>
</div>

<div class="customer-table-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
    <table class="customer-table" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px 8px;">Nama Layanan</th>
                <th style="padding: 12px 8px;">Tipe</th>
                <th style="padding: 12px 8px;">Harga</th>
                <th style="padding: 12px 8px;">Estimasi</th>
                <th style="padding: 12px 8px;">Status</th>
                <th style="padding: 12px 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 8px;"><strong>{{ $service->name }}</strong></td>
                    <td style="padding: 12px 8px;">{{ ucfirst($service->type) }}</td>
                    <td style="padding: 12px 8px;">Rp {{ number_format($service->price, 0, ',', '.') }} / {{ $service->type == 'kiloan' ? 'Kg' : 'Item' }}</td>
                    <td style="padding: 12px 8px;">{{ $service->estimated_hours }} Jam</td>
                    <td style="padding: 12px 8px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; {{ $service->is_active ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                            {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td style="padding: 12px 8px;">
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="open-edit-modal" style="background: none; border: none; cursor: pointer; font-size: 16px;"
                                data-id="{{ $service->id }}"
                                data-name="{{ $service->name }}"
                                data-type="{{ $service->type }}"
                                data-price="{{ $service->price }}"
                                data-hours="{{ $service->estimated_hours }}"
                                data-active="{{ $service->is_active }}">
                                ✎
                            </button>
                            <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Yakin ingin menghapus layanan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 16px; color: red;">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 20px;">Belum ada layanan tersedia.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- MODAL TAMBAH & EDIT --}}
<div class="modal-overlay" id="serviceModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div class="customer-modal-card" style="background: white; width: 400px; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <h3 id="modalTitle" style="margin: 0;">Tambah Layanan</h3>
            <button type="button" id="closeModalBtn" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="{{ route('services.store') }}" id="serviceForm">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 600;">Nama Layanan</label>
                <input type="text" name="name" id="serviceName" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; margin-top: 4px;">
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 600;">Tipe Hitungan</label>
                <select name="type" id="serviceType" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; margin-top: 4px;">
                    <option value="kiloan">Kiloan</option>
                    <option value="satuan">Satuan</option>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 600;">Harga (Rp)</label>
                <input type="number" name="price" id="servicePrice" min="0" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; margin-top: 4px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 600;">Estimasi Waktu Selesai (Jam)</label>
                <input type="number" name="estimated_hours" id="serviceHours" min="1" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; margin-top: 4px;">
            </div>

            <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" id="serviceActive" value="1" checked style="width: 16px; height: 16px;">
                <label style="font-size: 13px; font-weight: 600;">Layanan Aktif (Bisa dipesan)</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="cancelModalBtn" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 8px 16px; border-radius: 8px; border: none; background: #0ea5e9; color: white; font-weight: 600; cursor: pointer;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('serviceModal');
    const form = document.getElementById('serviceForm');
    const baseUrl = "{{ url('services') }}";

    // Buka Modal Tambah
    document.getElementById('openCreateModalBtn').addEventListener('click', () => {
        document.getElementById('modalTitle').innerText = 'Tambah Layanan';
        form.action = baseUrl;
        document.getElementById('formMethod').value = 'POST';
        form.reset();
        document.getElementById('serviceActive').checked = true;
        modal.style.display = 'flex';
    });

    // Buka Modal Edit
    document.querySelectorAll('.open-edit-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').innerText = 'Edit Layanan';
            form.action = `${baseUrl}/${this.dataset.id}`;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('serviceName').value = this.dataset.name;
            document.getElementById('serviceType').value = this.dataset.type;
            document.getElementById('servicePrice').value = this.dataset.price;
            document.getElementById('serviceHours').value = this.dataset.hours;
            document.getElementById('serviceActive').checked = this.dataset.active == "1";

            modal.style.display = 'flex';
        });
    });

    // Tutup Modal
    const closeModal = () => modal.style.display = 'none';
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    document.getElementById('cancelModalBtn').addEventListener('click', closeModal);
});
</script>
@endsection
