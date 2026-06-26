<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Service;
use App\Models\LaundryOrder;
use App\Models\Invoice;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class LaundryOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search);

        $orders = \App\Models\LaundryOrder::with(['customer.user', 'service'])
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

        // KEMBALIKAN VARIABEL INI: Dibutuhkan untuk form Modal "Tambah Pesanan"
        $customers = \App\Models\Customer::with('user')->get();
        $services = \App\Models\Service::where('is_active', true)->get();

        return view('orders.index', compact('orders', 'search', 'customers', 'services'));
    }

    public function create()
    {
        return redirect()->route('orders.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'service_id' => ['required', 'exists:services,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $amountBase = $service->type === 'kiloan'
            ? ($validated['weight'] ?? 0)
            : ($validated['quantity'] ?? 0);

        if ($amountBase <= 0) {
            return back()
                ->withErrors(['weight' => 'Masukkan berat atau jumlah cucian sesuai jenis layanan.'])
                ->withInput();
        }

        $subtotal = $amountBase * $service->price;
        $total = $subtotal;

        DB::transaction(function () use ($validated, $service, $subtotal, $total) {
            $order = LaundryOrder::create([
                'order_code' => 'ORD-' . now()->format('YmdHis'),
                'customer_id' => $validated['customer_id'],
                'service_id' => $validated['service_id'],
                'cashier_id' => Auth::id(),
                'weight' => $validated['weight'] ?? null,
                'quantity' => $validated['quantity'] ?? null,
                'subtotal' => $subtotal,
                'total_price' => $total,
                'status' => 'diterima',
                'payment_status' => 'belum_bayar',
            ]);

            Invoice::create([
                'laundry_order_id' => $order->id,
                'invoice_code' => 'INV-' . now()->format('YmdHis'),
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'status' => 'unpaid',
                'issued_at' => now(),
            ]);

            OrderStatusHistory::create([
                'laundry_order_id' => $order->id,
                'user_id' => Auth::id(),
                'status' => 'diterima',
                'note' => 'Order laundry dibuat oleh kasir/admin.',
            ]);
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'Transaksi laundry berhasil dibuat.');
    }

    public function show(LaundryOrder $order)
    {
        $order->load(['customer.user', 'service', 'cashier', 'invoice', 'statusHistories']);

        return view('orders.show', compact('order'));
    }
}
