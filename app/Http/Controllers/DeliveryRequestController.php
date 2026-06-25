<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\Invoice;
use App\Models\LaundryOrder;
use App\Models\OrderStatusHistory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryRequestController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search ?? '');

        $pickups = DeliveryRequest::with(['customer.user', 'service', 'laundryOrder'])
            ->where('type', 'jemput')
            ->when($search, function ($query) use ($search, $cleanSearch) {
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->where('address', 'like', "%{$search}%")
                        ->orWhereHas('customer.user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        });

                    if ($cleanSearch !== '') {
                        $q->orWhere('id', (int) $cleanSearch);
                    }
                });
            })
            ->latest()
            ->paginate(5, ['*'], 'pickup_page')
            ->appends($request->query());

        $deliveries = DeliveryRequest::with(['customer.user', 'service', 'laundryOrder'])
            ->where('type', 'antar')
            ->when($search, function ($query) use ($search, $cleanSearch) {
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->where('address', 'like', "%{$search}%")
                        ->orWhereHas('customer.user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        });

                    if ($cleanSearch !== '') {
                        $q->orWhere('id', (int) $cleanSearch);
                    }
                });
            })
            ->latest()
            ->paginate(5, ['*'], 'delivery_page')
            ->appends($request->query());

        return view('delivery.index', compact('pickups', 'deliveries', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'latitude' => ['required'],
            'longitude' => ['required'],
            'address_main' => ['required', 'string'],
            'address_detail' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
            'scheduled_at' => ['nullable', 'date'],
        ], [
            'service_id.required' => 'Silakan pilih jenis layanan terlebih dahulu.',
            'latitude.required' => 'Silakan tentukan titik lokasi pada peta.',
            'longitude.required' => 'Silakan tentukan titik lokasi pada peta.',
            'address_main.required' => 'Alamat utama wajib diisi.',
            'address_detail.required' => 'Detail patokan alamat wajib diisi.',
        ]);

        $customer = Auth::user()->customer;

        if (!$customer) {
            abort(403, 'Data pelanggan tidak ditemukan.');
        }

        $outletLat = -7.428940;
        $outletLng = 109.337930;

        $distance = $this->calculateDistance(
            $outletLat,
            $outletLng,
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        $pickupFee = $this->calculatePickupFee($distance);

        $fullAddress = $validated['address_main']
            . ' (Detail: '
            . $validated['address_detail']
            . ')';

        DeliveryRequest::create([
            'customer_id' => $customer->id,
            'laundry_order_id' => null,
            'service_id' => $validated['service_id'],
            'type' => 'jemput',
            'address' => $fullAddress,
            'distance_km' => $distance,
            'fee' => $pickupFee,
            'status' => 'menunggu_konfirmasi',
            'note' => $validated['note'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        return redirect()
            ->route('portal.active')
            ->with(
                'success',
                'Permintaan jemput berhasil dikirim! Jarak tercatat: '
                . $distance
                . ' KM (Biaya: Rp '
                . number_format($pickupFee, 0, ',', '.')
                . '). Silakan siapkan cucian Anda, kurir kami akan segera datang.'
            );
    }

    public function updateStatus(Request $request, DeliveryRequest $deliveryRequest)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:menunggu_konfirmasi,diproses,selesai,dibatalkan',
            ],
        ]);

        $deliveryRequest->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('delivery.index')
            ->with('success', 'Status permintaan jemput/antar berhasil diperbarui.');
    }

    public function confirm(Request $request, DeliveryRequest $deliveryRequest)
    {
        if ($deliveryRequest->type !== 'jemput') {
            return redirect()
                ->route('delivery.index')
                ->withErrors([
                    'type' => 'Hanya permintaan jemput yang dapat dibuat menjadi transaksi.',
                ]);
        }

        if ($deliveryRequest->laundry_order_id) {
            return redirect()
                ->route('delivery.index')
                ->with('success', 'Permintaan jemput ini sudah dibuatkan transaksi.');
        }

        if ($deliveryRequest->status !== 'selesai') {
            return redirect()
                ->route('delivery.index')
                ->withErrors([
                    'status' => 'Permintaan jemput harus berstatus selesai sebelum dibuat menjadi transaksi.',
                ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.1'],
        ]);

        $deliveryRequest->load(['customer', 'service']);

        $service = $deliveryRequest->service;

        if (!$service) {
            return redirect()
                ->route('delivery.index')
                ->withErrors([
                    'service_id' => 'Layanan pada permintaan jemput tidak ditemukan.',
                ]);
        }

        $amount = (float) $validated['amount'];

        $weight = $service->type === 'kiloan' ? $amount : null;
        $quantity = $service->type === 'satuan' ? (int) $amount : null;

        $subtotal = $amount * $service->price;
        $deliveryFee = $deliveryRequest->fee ?? 0;
        $discount = 0;
        $total = $subtotal + $deliveryFee - $discount;

        DB::transaction(function () use (
            $deliveryRequest,
            $service,
            $weight,
            $quantity,
            $subtotal,
            $deliveryFee,
            $discount,
            $total
        ) {
            $order = LaundryOrder::create([
                'order_code' => 'ORD-' . now()->format('YmdHis') . '-' . $deliveryRequest->id,
                'customer_id' => $deliveryRequest->customer_id,
                'service_id' => $service->id,
                'cashier_id' => Auth::id(),
                'weight' => $weight,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'total_price' => $total,
                'order_source' => 'portal',
                'delivery_option' => 'ambil_sendiri',
                'status' => 'diterima',
                'payment_status' => 'belum_bayar',
            ]);

            Invoice::create([
                'laundry_order_id' => $order->id,
                'invoice_code' => 'INV-' . now()->format('YmdHis') . '-' . $deliveryRequest->id,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'point_discount' => $discount,
                'total_amount' => $total,
                'status' => 'unpaid',
                'issued_at' => now(),
            ]);

            OrderStatusHistory::create([
                'laundry_order_id' => $order->id,
                'user_id' => Auth::id(),
                'status' => 'diterima',
                'note' => 'Transaksi dibuat dari permintaan jemput pelanggan.',
            ]);

            $deliveryRequest->update([
                'laundry_order_id' => $order->id,
                'status' => 'selesai',
            ]);
        });

        return redirect()
            ->route('delivery.index')
            ->with('success', 'Permintaan jemput berhasil dibuat menjadi transaksi resmi.');
    }

    public function requestDelivery(Request $request, LaundryOrder $order)
    {
        $validated = $request->validate([
            'latitude' => ['required'],
            'longitude' => ['required'],
            'address_main' => ['required', 'string'],
            'address_detail' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'latitude.required' => 'Silakan tentukan titik lokasi pengantaran pada peta.',
            'longitude.required' => 'Silakan tentukan titik lokasi pengantaran pada peta.',
            'address_main.required' => 'Alamat utama wajib diisi.',
            'address_detail.required' => 'Detail patokan alamat wajib diisi.',
        ]);

        $customer = Auth::user()->customer;

        if (!$customer || $order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke order ini.');
        }

        if ($order->status !== 'siap_diambil') {
            return redirect()
                ->back()
                ->withErrors([
                    'status' => 'Layanan antar hanya tersedia ketika cucian sudah siap diambil.',
                ]);
        }

        if (now()->hour >= 18) {
            return redirect()
                ->back()
                ->withErrors([
                    'time' => 'Layanan pengantaran sudah tidak tersedia setelah pukul 18.00 WIB.',
                ]);
        }

        if ($order->delivery_option === 'antar') {
            return redirect()
                ->back()
                ->with('success', 'Permintaan pengantaran untuk order ini sudah dibuat.');
        }

        $order->load('invoice');

        if (!$order->invoice) {
            return redirect()
                ->back()
                ->withErrors([
                    'invoice' => 'Invoice untuk order ini tidak ditemukan.',
                ]);
        }

        if ($order->invoice->status === 'paid') {
            return redirect()
                ->back()
                ->withErrors([
                    'payment' => 'Invoice sudah lunas. Permintaan antar setelah pembayaran harus diproses oleh kasir.',
                ]);
        }

        $outletLat = -7.428940;
        $outletLng = 109.337930;

        $distance = $this->calculateDistance(
            $outletLat,
            $outletLng,
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        $deliveryFee = $this->calculateDeliveryFee($distance);

        $fullAddress = $validated['address_main']
            . ' (Detail: '
            . $validated['address_detail']
            . ')';

        DB::transaction(function () use ($order, $customer, $validated, $distance, $deliveryFee, $fullAddress) {
            DeliveryRequest::create([
                'customer_id' => $customer->id,
                'laundry_order_id' => $order->id,
                'service_id' => $order->service_id,
                'type' => 'antar',
                'address' => $fullAddress,
                'distance_km' => $distance,
                'fee' => $deliveryFee,
                'status' => 'menunggu_konfirmasi',
                'note' => $validated['note'] ?? null,
                'scheduled_at' => now(),
            ]);

            $invoice = $order->invoice;

            $newTotal = ($invoice->subtotal ?? 0)
                + $deliveryFee
                - ($invoice->point_discount ?? 0);

            $invoice->update([
                'delivery_fee' => $deliveryFee,
                'total_amount' => max(0, $newTotal),
            ]);

            $order->update([
                'delivery_option' => 'antar',
                'delivery_fee' => $deliveryFee,
                'total_price' => max(0, ($order->subtotal ?? 0) + $deliveryFee - ($order->discount ?? 0)),
            ]);
        });

        return redirect()
            ->route('portal.orders.show', $order)
            ->with(
                'success',
                'Permintaan antar berhasil dikirim. Jarak tercatat: '
                . $distance
                . ' KM. Biaya antar: Rp '
                . number_format($deliveryFee, 0, ',', '.')
                . '.'
            );
    }

    private function calculateDistance(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): float {
        $earthRadius = 6371;

        $latFrom = deg2rad($fromLat);
        $lonFrom = deg2rad($fromLng);
        $latTo = deg2rad($toLat);
        $lonTo = deg2rad($toLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFrom) * cos($latTo)
            * sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    private function calculatePickupFee(float $distanceKm): float
    {
        $basePickupFee = Setting::getNumber('pickup_fee', 0);
        $freeDistance = Setting::getNumber('free_delivery_distance_km', 3);
        $feePerKm = Setting::getNumber('delivery_fee_per_km', 2000);

        if ($distanceKm <= $freeDistance) {
            return $basePickupFee;
        }

        $chargedDistance = ceil($distanceKm - $freeDistance);

        return $basePickupFee + ($chargedDistance * $feePerKm);
    }

    private function calculateDeliveryFee(float $distanceKm): float
    {
        $freeDistance = Setting::getNumber('free_delivery_distance_km', 3);
        $feePerKm = Setting::getNumber('delivery_fee_per_km', 2000);

        if ($distanceKm <= $freeDistance) {
            return 0;
        }

        $chargedDistance = ceil($distanceKm - $freeDistance);

        return $chargedDistance * $feePerKm;
    }
}
