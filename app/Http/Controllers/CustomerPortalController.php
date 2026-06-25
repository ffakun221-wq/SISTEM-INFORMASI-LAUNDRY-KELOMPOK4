<?php

namespace App\Http\Controllers;

use App\Models\LaundryOrder;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Setting;
class CustomerPortalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'Data pelanggan tidak ditemukan.');
        }

        $activeOrders = LaundryOrder::with(['service', 'invoice'])
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['selesai', 'dibatalkan'])
            ->latest()
            ->get();

        $completedOrders = LaundryOrder::with(['service', 'invoice'])
            ->where('customer_id', $customer->id)
            ->where('status', 'selesai')
            ->latest()
            ->get();

        $recentOrders = LaundryOrder::with(['service', 'invoice'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();

        $services = Service::where('is_active', true)->get();

        return view('portal.dashboard', compact(
            'customer',
            'activeOrders',
            'completedOrders',
            'recentOrders',
            'services'
        ));
    }

    public function show(LaundryOrder $order)
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer || $order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke order ini.');
        }

        $order->load(['service', 'invoice', 'statusHistories']);

        $freeDeliveryDistance = Setting::getNumber('free_delivery_distance_km', 3);
        $deliveryFeePerKm = Setting::getNumber('delivery_fee_per_km', 2000);

        return view('portal.show', compact(
            'order',
            'customer',
            'freeDeliveryDistance',
            'deliveryFeePerKm'
        ));
    }

    public function active()
    {
        $customer = Auth::user()->customer;

        // 1. Ambil transaksi laundry yang sedang berjalan (seperti biasa)
        $activeOrders = LaundryOrder::where('customer_id', $customer->id)
            ->whereNotIn('status', ['selesai', 'dibatalkan'])
            ->latest()
            ->get();

        // 2. TAMBAHAN: Ambil permintaan jemput yang BELUM jadi transaksi resmi
        $pendingPickups = \App\Models\DeliveryRequest::where('customer_id', $customer->id)
            ->where('type', 'jemput')
            ->whereNull('laundry_order_id') // Artinya belum dikonfirmasi/ditimbang kasir
            ->whereNotIn('status', ['selesai', 'dibatalkan'])
            ->latest()
            ->get();

        return view('portal.active', compact('activeOrders', 'pendingPickups'));
    }

    public function history()
    {
        $customer = Auth::user()->customer;
        $completedOrders = LaundryOrder::with(['service', 'invoice'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['selesai', 'dibatalkan'])
            ->latest()
            ->get();

        return view('portal.history', compact('completedOrders'));
    }

    public function points()
    {
        $customer = Auth::user()->customer;

        // Menggunakan nama kelas absolut untuk menghindari error Namespace Not Found
        $pointTransactions = \App\Models\PointTransaction::where('customer_id', $customer->id)
            ->latest()
            ->get();

        return view('portal.points', compact('customer', 'pointTransactions'));
    }

    public function account()
    {
        $user = Auth::user();
        $customer = $user->customer;

        return view('portal.account', compact('user', 'customer'));
    }

    public function updateAccount(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'    => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'address'  => 'required|string',
            'password_sekarang' => 'nullable|required_with:password_baru',
            'password_baru'     => 'nullable|min:8|confirmed',
        ], [
            'email.unique' => 'Email ini sudah dipakai.',
            'phone.unique' => 'Nomor WhatsApp ini sudah dipakai.',
        ]);

        // Cek Keamanan jika ingin ganti password
        if ($request->filled('password_sekarang')) {
            if (!Hash::check($request->password_sekarang, $user->password)) {
                return back()->withErrors(['password_sekarang' => 'Password saat ini salah.']);
            }
            $user->password = Hash::make($request->password_baru);
        }

        // Update tabel users
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        // Update tabel customers
        $customer->update([
            'phone'   => $request->phone,
            'address' => $request->address,
        ]);

        return back()->with('success', 'Informasi akun berhasil diperbarui!');
    }

    public function createPickup()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'Data pelanggan tidak ditemukan.');
        }

        // Mengambil layanan laundry yang aktif untuk ditampilkan dalam form dropdown select
        $services = Service::where('is_active', true)->get();

        return view('portal.create_pickup', compact('customer', 'services'));
    }
    public function requestDelivery(Request $request, LaundryOrder $order)
    {
        // 1. Validasi Keamanan dan Ketersediaan Peta
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'address_main' => 'required',
        ], [
            'latitude.required' => 'Silakan tentukan titik lokasi pada peta terlebih dahulu.',
        ]);

        $customer = Auth::user()->customer;

        if (!$customer || $order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke order ini.');
        }

        $jamSekarang = now()->timezone('Asia/Jakarta')->format('H:i');
        if ($jamSekarang >= '18:00') {
            return back()->with('info', 'Maaf, layanan antar tutup pada pukul 18:00 WIB. Silakan ambil pesanan Anda secara mandiri.');
        }

        // Mencegah pelanggan memanipulasi sistem dengan mengirim pengajuan dobel
        $hasActiveDelivery = \App\Models\DeliveryRequest::where('laundry_order_id', $order->id)
            ->where('type', 'antar')
            ->where('status', '!=', 'dibatalkan')
            ->exists();

        if ($hasActiveDelivery) {
            return back()->with('info', 'Anda sudah memiliki jadwal pengantaran yang aktif untuk pesanan ini.');
        }

        // 2. Rumus Haversine: Menghitung Jarak Lurus Bumi (KM)
        $outletLat = -7.428940;  // Koordinat Latitude Laundry (PASTIKAN INI SESUAI OUTLET ANDA)
        $outletLng = 109.337930; // Koordinat Longitude Laundry (PASTIKAN INI SESUAI OUTLET ANDA)

        $earthRadius = 6371; // Radius bumi dalam KM

        // Memaksa konversi tipe data ke float agar tidak kacau
        $latFrom = deg2rad((float) $outletLat);
        $lonFrom = deg2rad((float) $outletLng);
        $latTo = deg2rad((float) $request->latitude);
        $lonTo = deg2rad((float) $request->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = round($earthRadius * $c, 2);

        // 3. Logika Biaya Tambahan Jarak
        $fee = 0;
        if ($distance > 2) {
            $kelebihanKm = ceil($distance - 2);
            $fee = $kelebihanKm * 3000;
        }

        $fullAddress = $request->address_main;
        if ($request->filled('address_detail')) {
            $fullAddress .= ' (Detail: ' . $request->address_detail . ')';
        }

        // 4. Mulai Transaksi Database (Mencegah data setengah masuk jika ada error)
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // A. Buat Request Delivery
            \App\Models\DeliveryRequest::create([
                'customer_id' => $customer->id,
                'laundry_order_id' => $order->id,
                'type' => 'antar',
                'address' => $fullAddress,
                'distance_km' => $distance,
                'fee' => $fee,
                'status' => 'menunggu_konfirmasi',
            ]);

            // B. Perbarui Data Order Utama
            $order->delivery_fee = $fee;
            // Rumus: subtotal - diskon + biaya antar
            $order->total_price = ($order->subtotal ?? 0) - ($order->discount ?? 0) + $fee;
            $order->save();

            // C. Perbarui Data Invoice (Jika sudah di-generate)
            if ($order->invoice) {
                $order->invoice->delivery_fee = $fee;
                $order->invoice->total_amount = ($order->invoice->subtotal ?? 0) - ($order->invoice->point_discount ?? 0) + $fee;
                $order->invoice->save();
            }

            \Illuminate\Support\Facades\DB::commit();

            return back()->with('success', "Permintaan pengantaran berhasil diajukan! Jarak tercatat: {$distance} KM. Biaya Antar: Rp " . number_format($fee, 0, ',', '.'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('info', 'Terjadi kesalahan sistem saat memproses biaya antar. Silakan coba lagi.');
        }
    }
    public function cancelDelivery(LaundryOrder $order)
    {
        $customer = Auth::user()->customer;

        if (!$customer || $order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke order ini.');
        }

        // Ambil SEMUA request pengantaran yang menggantung (untuk menyapu bersih 'data hantu')
        $deliveryRequests = \App\Models\DeliveryRequest::where('laundry_order_id', $order->id)
            ->where('type', 'antar')
            ->where('status', 'menunggu_konfirmasi')
            ->get();

        if ($deliveryRequests->isEmpty()) {
            return back()->with('info', 'Pembatalan gagal. Pengantaran mungkin sudah diproses oleh kurir atau data tidak ditemukan.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Batalkan semua request pengantaran yang ditemukan di database
            foreach ($deliveryRequests as $request) {
                $request->status = 'dibatalkan';
                $request->save();
            }

            // 2. KEMBALIKAN HARGA DAN STATUS SECARA MUTLAK
            // Alih-alih mengurangi (yang bisa error jika ditekan berkali-kali),
            // kita paksakan harganya kembali ke rumus asli: Subtotal - Diskon.
            $order->delivery_fee = 0;
            $order->total_price = ($order->subtotal ?? 0) - ($order->discount ?? 0);
            $order->delivery_option = 'ambil_sendiri';
            $order->save();

            // 3. Kembalikan juga Invoice secara mutlak
            if ($order->invoice) {
                $order->invoice->delivery_fee = 0;
                $order->invoice->total_amount = ($order->invoice->subtotal ?? 0) - ($order->invoice->point_discount ?? 0);
                $order->invoice->save();
            }

            \Illuminate\Support\Facades\DB::commit();

            return back()->with('success', 'Permintaan pengantaran berhasil dibatalkan. Tagihan Anda telah kembali normal.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('info', 'Terjadi kesalahan sistem saat membatalkan pengantaran.');
        }
    }
    public function editProfile()
    {
        $customer = Auth::user()->customer;
        return view('portal.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'phone'    => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'address'  => 'required|string',
            'password' => 'nullable|min:8',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $customer->update([
            'phone'   => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->back()->with('success', 'Profil Anda berhasil diperbarui!');
    }

    public function cancelPickup(\App\Models\DeliveryRequest $deliveryRequest)
    {
        // 1. Keamanan: Pastikan data jemput ini benar-benar milik pelanggan yang sedang login
        $customer = Auth::user()->customer;
        if ($deliveryRequest->customer_id !== $customer->id) {
            abort(403, 'Akses ditolak.');
        }

        // 2. Keamanan UX: Pastikan statusnya masih "menunggu_konfirmasi"
        if ($deliveryRequest->status !== 'menunggu_konfirmasi') {
            return back()->with('error', 'Gagal membatalkan! Kurir sudah diproses atau sedang dalam perjalanan ke lokasi Anda.');
        }

        // 3. Batalkan pesanan
        $deliveryRequest->update([
            'status' => 'dibatalkan',
        ]);

        return back()->with('success', 'Permintaan penjemputan cucian berhasil dibatalkan.');
    }
}
