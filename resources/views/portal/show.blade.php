@extends('layouts.portal')

@section('content')
<section class="page-header portal-page-header">
    <h1>Detail Pesanan #ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</h1>
    <p>Informasi status, invoice, dan opsi pengiriman cucian Anda.</p>
</section>

@if(session('success'))
    <div class="alert-success" style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
        {{ session('success') }}
    </div>
@endif

@if(session('info'))
    <div style="margin-bottom: 20px; padding: 14px; border-radius: 10px; background: #fee2e2; color: #991b1b; font-weight: 600; border: 1px solid #fecaca;">
        {{ session('info') }}
    </div>
@endif

<div class="portal-detail-grid">
    <div class="portal-profile-card">
        <h3>Informasi Order</h3>

        <div class="portal-profile-row">
            <span>No. Order</span>
            <strong>ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Layanan</span>
            <strong>{{ $order->service->name ?? '-' }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Status Cucian</span>
            <strong>{{ ucwords(str_replace('_', ' ', $order->status)) }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Status Pembayaran</span>
            <strong>{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Metode Pembayaran</span>
            <strong style="color: #0ea5e9;">
                @if($order->invoice && $order->invoice->payment)
                    {{ strtoupper($order->invoice->payment->method ?? $order->invoice->payment->payment_method ?? '-') }}
                @else
                    Belum Dibayar
                @endif
            </strong>
        </div>
    </div>

    <div class="portal-profile-card">
        <h3>Rincian Invoice</h3>

        <div class="portal-profile-row">
            <span>Subtotal</span>
            <strong>Rp {{ number_format($order->invoice->subtotal ?? 0, 0, ',', '.') }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Biaya Antar</span>
            <strong>Rp {{ number_format($order->invoice->delivery_fee ?? 0, 0, ',', '.') }}</strong>
        </div>

        <div class="portal-profile-row">
            <span>Diskon Poin</span>
            <strong>Rp {{ number_format($order->invoice->point_discount ?? 0, 0, ',', '.') }}</strong>
        </div>

        <div class="portal-profile-row total">
            <span>Total</span>
            <strong>Rp {{ number_format($order->invoice->total_amount ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>
</div>

<div class="portal-order-card" style="margin-top: 24px;">
    <h3>Riwayat Status</h3>

    <table class="portal-order-table">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->statusHistories as $history)
                <tr>
                    <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $history->status)) }}</td>
                    <td>{{ $history->note ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty-row">Belum ada riwayat status.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@php
    $isDeliveryAvailable = now()->timezone('Asia/Jakarta')->format('H:i') < '18:00';
    $canRequest = $order->status === 'siap_diambil';

    // Cek apakah user sudah memiliki permintaan pengantaran aktif (yang belum dibatalkan)
    $activeDelivery = \App\Models\DeliveryRequest::where('laundry_order_id', $order->id)
        ->where('type', 'antar')
        ->where('status', '!=', 'dibatalkan')
        ->first();
@endphp

@if($canRequest)
    @if($activeDelivery)
        <div class="portal-pickup-card" style="margin-top: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0ea5e9;">Cucian Akan Diantar 🚚</h3>
                    <p style="margin: 0; color: #64748b; font-size: 14px;"><strong>Alamat:</strong> {{ $activeDelivery->address }}</p>
                    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 14px;"><strong>Jarak:</strong> {{ $activeDelivery->distance_km }} KM | <strong>Biaya:</strong> Rp {{ number_format($activeDelivery->fee, 0, ',', '.') }}</p>
                </div>

                @if($activeDelivery->status === 'menunggu_konfirmasi')
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 6px 12px; background: #fef3c7; color: #b45309; border-radius: 6px; font-size: 13px; font-weight: 700; margin-bottom: 12px;">
                            Menunggu Konfirmasi Kasir
                        </span>

                        <form action="{{ route('portal.orders.cancel_delivery', $order->id) }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan pengantaran? Biaya tagihan akan disesuaikan kembali.')" style="background: #ef4444; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; display: block; width: 100%;">
                                Batalkan Pengantaran
                            </button>
                        </form>
                    </div>
                @else
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 6px 12px; background: #dcfce7; color: #166534; border-radius: 6px; font-size: 13px; font-weight: 700;">
                            Pengantaran Sedang Diproses / Selesai
                        </span>
                        <p style="color: #64748b; font-size: 12px; margin-top: 8px; max-width: 200px;">
                            *Pengantaran sudah tidak dapat dibatalkan karena kurir telah ditugaskan.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    @else
        <div class="portal-pickup-card" style="margin-top: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px;">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 800;">Opsi Pengiriman Cucian</h3>
                <p style="margin: 0; color: #64748b;">Geser pin pada peta untuk menentukan lokasi, jarak, dan biaya antar.</p>

                @if(!$isDeliveryAvailable)
                    <p style="color: #dc2626; font-size: 13px; font-weight: 600; margin-top: 8px;">
                        *Layanan kurir tutup. Permintaan antar hanya dilayani hingga pukul 18:00 WIB.
                    </p>
                @endif
            </div>

            @if($isDeliveryAvailable)
                <form id="delivery-form" method="POST" action="{{ route('portal.delivery.request', $order->id) }}" style="display: flex; flex-direction: column; gap: 16px; margin: 0;">
                    @csrf

                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="search-map-input" placeholder="Cari nama jalan, desa, atau kota..." style="flex: 1; height: 42px; border: 1px solid #d5dde8; border-radius: 8px; padding: 0 12px; font-size: 13px; outline: none;">
                        <button type="button" id="search-map-btn" style="background: #0f172a; color: white; border: none; border-radius: 8px; padding: 0 18px; font-weight: 700; cursor: pointer; font-size: 13px;">Cari Lokasi</button>
                    </div>

                    <div id="map" style="height: 250px; width: 100%; border-radius: 10px; z-index: 1; border: 1px solid #cbd5e1;"></div>
                    <div id="delivery-preview" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                        <div>
                            <p style="margin: 0 0 4px; color: #64748b; font-size: 12px; font-weight: 700;">Estimasi Jarak</p>
                            <strong id="delivery-distance-preview" style="font-size: 16px; color: #0f172a;">0 KM</strong>
                        </div>

                        <div>
                            <p style="margin: 0 0 4px; color: #64748b; font-size: 12px; font-weight: 700;">Estimasi Biaya Antar</p>
                            <strong id="delivery-fee-preview" style="font-size: 16px; color: #0284c7;">Rp 0</strong>
                        </div>
                    </div>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-size: 13px; font-weight: 700; color: #334155;">Alamat Terdeteksi Otomatis (Tidak Bisa Diubah):</label>
                        <textarea name="address_main" id="address_main" readonly required placeholder="Geser pin di peta, alamat otomatis terisi..." style="width: 100%; min-height: 60px; background: #f1f5f9; border: 1px solid #d5dde8; border-radius: 8px; padding: 12px; resize: none; font-size: 13px; outline: none;"></textarea>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-size: 13px; font-weight: 700; color: #334155;">Detail Tambahan Patokan (Wajib):</label>
                        <input type="text" name="address_detail" required placeholder="Contoh: Rumah tingkat pagar hitam depan warung ibu Yati" style="width: 100%; height: 42px; border: 1px solid #d5dde8; border-radius: 8px; padding: 0 12px; outline: none; font-size: 13px;">
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                        <button type="submit" class="portal-pickup-btn" onclick="return confirm('Apakah alamat dan titik peta sudah sesuai?')" style="background: #16a34a; height: 42px; padding: 0 24px; border-radius: 10px; color: #fff; font-weight: 700; border: 0; cursor: pointer;">
                            🚚 Konfirmasi & Minta Diantar
                        </button>
                    </div>
                </form>
            @else
                <button type="button" class="portal-pickup-btn" style="background: #94a3b8; color: #f1f5f9; white-space: nowrap; height: 42px; padding: 0 18px; border-radius: 10px; font-weight: 700; border: 0; cursor: not-allowed;" disabled>
                    🚚 Minta Diantar (Tutup)
                </button>
            @endif
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var outletLat = -7.428940;
            var outletLng = 109.337930;

            const freeDistance = Number(@json($freeDeliveryDistance ?? 3));
            const feePerKm = Number(@json($deliveryFeePerKm ?? 2000));

            var map = L.map('map').setView([outletLat, outletLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker = L.marker([outletLat, outletLng], { draggable: true }).addTo(map);

            function calculateDistance(fromLat, fromLng, toLat, toLng) {
                const earthRadius = 6371;

                const latFrom = degToRad(fromLat);
                const lngFrom = degToRad(fromLng);
                const latTo = degToRad(toLat);
                const lngTo = degToRad(toLng);

                const latDelta = latTo - latFrom;
                const lngDelta = lngTo - lngFrom;

                const a = Math.sin(latDelta / 2) * Math.sin(latDelta / 2)
                    + Math.cos(latFrom) * Math.cos(latTo)
                    * Math.sin(lngDelta / 2) * Math.sin(lngDelta / 2);

                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                return earthRadius * c;
            }

            function degToRad(value) {
                return value * Math.PI / 180;
            }

            function calculateDeliveryFee(distanceKm) {
                if (distanceKm <= freeDistance) {
                    return 0;
                }

                const chargedDistance = Math.ceil(distanceKm - freeDistance);
                return chargedDistance * feePerKm;
            }

            function formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value);
            }

            function updateDeliveryPreview(lat, lng) {
                const distance = calculateDistance(outletLat, outletLng, Number(lat), Number(lng));
                const roundedDistance = Number(distance.toFixed(2));
                const fee = calculateDeliveryFee(roundedDistance);

                const distancePreview = document.getElementById('delivery-distance-preview');
                const feePreview = document.getElementById('delivery-fee-preview');

                if (distancePreview) {
                    distancePreview.textContent = roundedDistance + ' KM';
                }

                if (feePreview) {
                    feePreview.textContent = 'Rp ' + formatRupiah(fee);
                }
            }

            function updateLocation(lat, lng) {
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                document.getElementById('address_main').value = 'Sedang mencari alamat otomatis...';

                updateDeliveryPreview(lat, lng);

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('address_main').value = data.display_name;
                        } else {
                            document.getElementById('address_main').value = "Alamat spesifik tidak ditemukan. Silakan tambahkan detail di kolom bawah.";
                        }
                    })
                    .catch(error => {
                        document.getElementById('address_main').value = "Gagal memuat alamat. Pastikan Anda memiliki koneksi internet.";
                    });
            }

            updateLocation(outletLat, outletLng);

            marker.on('dragend', function () {
                var position = marker.getLatLng();
                updateLocation(position.lat, position.lng);
            });

            var searchInput = document.getElementById('search-map-input');
            var searchBtn = document.getElementById('search-map-btn');

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchBtn.click();
                }
            });

            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();

                var query = searchInput.value;

                if (!query) {
                    return;
                }

                searchBtn.innerText = 'Mencari...';

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        searchBtn.innerText = 'Cari Lokasi';

                        if (data && data.length > 0) {
                            var lat = data[0].lat;
                            var lon = data[0].lon;

                            map.setView([lat, lon], 16);
                            marker.setLatLng([lat, lon]);
                            updateLocation(lat, lon);
                        } else {
                            alert('Lokasi tidak ditemukan. Coba gunakan nama desa/kecamatan yang lebih spesifik.');
                        }
                    })
                    .catch(err => {
                        searchBtn.innerText = 'Cari Lokasi';
                        alert('Terjadi kesalahan jaringan.');
                    });
            });
        });
        </script>
    @endif
@endif

<div class="portal-actions" style="margin-top: 24px;">
    <a href="{{ route('portal.active') }}" class="btn-cancel">Kembali</a>
</div>
@endsection
