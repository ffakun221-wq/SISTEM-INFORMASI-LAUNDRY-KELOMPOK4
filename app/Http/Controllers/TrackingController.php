<?php

namespace App\Http\Controllers;

use App\Models\LaundryOrder;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search ?? '');

        $orders = \App\Models\LaundryOrder::with(['customer.user', 'service'])
            ->whereNotIn('status', ['selesai', 'dibatalkan'])
            ->when($search, function ($query) use ($search, $cleanSearch) {
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->whereHas('customer.user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
                    if ($cleanSearch !== '') {
                        $q->orWhere('id', (int) $cleanSearch);
                    }
                });
            })
            ->latest()
            ->paginate(10)->appends(request()->query());

        // KEMBALIKAN VARIABEL INI: Dibutuhkan untuk opsi dropdown ubah status
        $statusOptions = [
            'diterima' => 'Diterima',
            'dicuci' => 'Sedang Dicuci',
            'dijemur' => 'Sedang Dijemur',
            'disetrika' => 'Sedang Disetrika',
            'siap_diambil' => 'Siap Diambil / Diantar',
            'selesai' => 'Selesai (Sudah Diserahkan)'
        ];

        return view('tracking.index', compact('orders', 'search', 'statusOptions'));
    }

    public function updateStatus(Request $request, LaundryOrder $order)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:diterima,dicuci,dijemur,disetrika,siap_diambil,selesai',
            ],
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $waSent = false;
        $waWarning = null;

        DB::transaction(function () use ($order, $oldStatus, $newStatus, &$waSent, &$waWarning) {
            // 1. Update status order
            $order->update([
                'status' => $newStatus,
            ]);

            // 2. Simpan riwayat status agar timestamp tercatat
            OrderStatusHistory::create([
                'laundry_order_id' => $order->id,
                'user_id' => Auth::id(),
                'status' => $newStatus,
                'note' => 'Status diubah dari ' . str_replace('_', ' ', $oldStatus) . ' menjadi ' . str_replace('_', ' ', $newStatus) . '.',
            ]);

            // 3. Kirim/log notifikasi WhatsApp hanya saat status siap_diambil
            if ($newStatus === 'siap_diambil') {
                $order->load(['customer.user']);

                $customer = $order->customer;
                $phone = $customer->phone ?? null;

                if (!$customer || !$phone) {
                    $waWarning = 'Nomor HP pelanggan tidak tersedia.';

                    NotificationLog::create([
                        'laundry_order_id' => $order->id,
                        'customer_id' => $customer->id ?? null,
                        'channel' => 'whatsapp',
                        'recipient' => $phone ?? '-',
                        'message' => 'Notifikasi tidak dikirim karena nomor HP pelanggan tidak tersedia.',
                        'status' => 'failed',
                        'error_message' => $waWarning,
                    ]);

                    return;
                }

                $name = $customer->user->name ?? 'Pelanggan';
                $orderId = 'ORD-' . str_pad($order->id, 3, '0', STR_PAD_LEFT);
                $total = number_format($order->total_price, 0, ',', '.');

                $pickupInfo = "Silakan ambil cucian di outlet. Jika tersedia, Anda juga dapat memilih opsi pengantaran melalui portal pelanggan.";

                $message = "Halo *{$name}*!\n\n"
                    . "Cucian Anda dengan No. Order *{$orderId}* sudah *SIAP DIAMBIL*.\n\n"
                    . "Total Tagihan: Rp {$total}\n"
                    . "{$pickupInfo}\n\n"
                    . "Terima kasih telah menggunakan layanan Laundry System.";

                $token = env('FONNTE_TOKEN');

                if (!$token) {
                    $waWarning = 'Token Fonnte belum diisi di file .env.';

                    NotificationLog::create([
                        'laundry_order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'channel' => 'whatsapp',
                        'recipient' => $phone,
                        'message' => $message,
                        'status' => 'failed',
                        'error_message' => $waWarning,
                    ]);

                    return;
                }

                try {
                    $response = Http::withHeaders([
                        'Authorization' => $token,
                    ])->post('https://api.fonnte.com/send', [
                        'target' => $phone,
                        'message' => $message,
                        'countryCode' => '62',
                    ]);

                    $responseData = $response->json();

                    if ($response->successful() && isset($responseData['status']) && $responseData['status']) {
                        $waSent = true;

                        NotificationLog::create([
                            'laundry_order_id' => $order->id,
                            'customer_id' => $customer->id,
                            'channel' => 'whatsapp',
                            'recipient' => $phone,
                            'message' => $message,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    } else {
                        $waWarning = $responseData['reason'] ?? 'API WhatsApp mengembalikan respons gagal.';

                        NotificationLog::create([
                            'laundry_order_id' => $order->id,
                            'customer_id' => $customer->id,
                            'channel' => 'whatsapp',
                            'recipient' => $phone,
                            'message' => $message,
                            'status' => 'failed',
                            'error_message' => $waWarning,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $waWarning = $e->getMessage();

                    NotificationLog::create([
                        'laundry_order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'channel' => 'whatsapp',
                        'recipient' => $phone,
                        'message' => $message,
                        'status' => 'failed',
                        'error_message' => $waWarning,
                    ]);
                }
            }
        });

        if ($newStatus === 'siap_diambil' && !$waSent) {
            return redirect()
                ->route('tracking.index')
                ->with('info', 'Status berhasil diperbarui, tetapi notifikasi WhatsApp gagal: ' . ($waWarning ?? 'penyebab tidak diketahui.'));
        }

        return redirect()
            ->route('tracking.index')
            ->with('success', 'Status cucian berhasil diperbarui.');
    }

}
