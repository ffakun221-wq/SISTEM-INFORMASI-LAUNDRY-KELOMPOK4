<?php

namespace App\Http\Controllers;

use App\Models\LaundryOrder;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search ?? '');

        $orders = LaundryOrder::with(['customer.user', 'service'])
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
            ->paginate(10)
            ->appends($request->query());

        $statusOptions = [
            'diterima' => 'Diterima',
            'dicuci' => 'Sedang Dicuci',
            'dijemur' => 'Sedang Dijemur',
            'disetrika' => 'Sedang Disetrika',
            'siap_diambil' => 'Siap Diambil',
            'selesai' => 'Selesai',
        ];

        return view('tracking.index', compact('orders', 'search', 'statusOptions'));
    }

    public function updateStatus(Request $request, LaundryOrder $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:diterima,dicuci,dijemur,disetrika,siap_diambil,selesai'],
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            $order->update([
                'status' => $newStatus,
            ]);

            OrderStatusHistory::create([
                'laundry_order_id' => $order->id,
                'user_id' => Auth::id(),
                'status' => $newStatus,
                'note' => 'Status diubah dari ' . str_replace('_', ' ', $oldStatus) . ' menjadi ' . str_replace('_', ' ', $newStatus) . '.',
            ]);
        });

        return redirect()
            ->route('tracking.index')
            ->with('success', 'Status cucian berhasil diperbarui.');
    }
}
