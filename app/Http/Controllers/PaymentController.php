<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search ?? '');

        $invoices = Invoice::with([
                'laundryOrder.customer.user',
                'laundryOrder.service',
                'payment',
            ])
            ->when($search, function ($query) use ($search, $cleanSearch) {
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->where('invoice_code', 'like', "%{$search}%")
                    ->orWhereHas('laundryOrder.customer.user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });

                    if ($cleanSearch !== '') {
                        $q->orWhere('laundry_order_id', (int) $cleanSearch);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        $paidInvoice = null;

        if ($request->filled('paid')) {
            $paidInvoice = Invoice::with([
                    'laundryOrder.customer.user',
                    'payment',
                ])
                ->find($request->paid);
        }

        $point = Setting::getNumber('point_value_rupiah', 100);

        return view('payments.index', compact('invoices', 'search', 'paidInvoice', 'point'));
    }

    public function process(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'method' => ['required', 'in:cash,qris,transfer'],
            'points_used' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($invoice->status === 'paid') {
            return redirect()
                ->route('payments.index')
                ->with('success', 'Invoice ini sudah lunas.');
        }

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->load('laundryOrder.customer');

            $order = $invoice->laundryOrder;
            $customer = $order->customer;

            $pointsUsed = (int) ($validated['points_used'] ?? 0);
            $availablePoints = (int) ($customer->points_balance ?? 0);
            $validPointsUsed = min($pointsUsed, $availablePoints);

            $pointValue = Setting::getNumber('point_value_rupiah', 100);
            $pointDiscount = $validPointsUsed * $pointValue;

            $baseTotal = ($invoice->subtotal ?? 0) + ($invoice->delivery_fee ?? 0);
            $finalTotal = max(0, $baseTotal - $pointDiscount);

            $pointEarnNominal = Setting::getNumber('point_earn_nominal', 10000);
            $earnedPoints = $pointEarnNominal > 0
                ? (int) floor($finalTotal / $pointEarnNominal)
                : 0;

            $invoice->update([
                'point_discount' => $pointDiscount,
                'total_amount' => $finalTotal,
                'status' => 'paid',
            ]);

            Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'method' => $validated['method'],
                'amount_paid' => $finalTotal,
                'change_amount' => 0,
                'paid_at' => now(),
            ]);

            $order->update([
                'discount' => $pointDiscount,
                'total_price' => $finalTotal,
                'payment_status' => 'dibayar',
            ]);

            if ($validPointsUsed > 0) {
                $customer->decrement('points_balance', $validPointsUsed);

                PointTransaction::create([
                    'customer_id' => $customer->id,
                    'laundry_order_id' => $order->id,
                    'type' => 'redeem',
                    'points' => $validPointsUsed,
                    'description' => 'Penukaran poin untuk pembayaran invoice ' . $invoice->invoice_code,
                ]);
            }

            if ($earnedPoints > 0) {
                $customer->increment('points_balance', $earnedPoints);

                PointTransaction::create([
                    'customer_id' => $customer->id,
                    'laundry_order_id' => $order->id,
                    'type' => 'earn',
                    'points' => $earnedPoints,
                    'description' => 'Poin dari pembayaran invoice ' . $invoice->invoice_code,
                ]);
            }
        });

        return redirect()
            ->route('payments.index', ['paid' => $invoice->id])
            ->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }
}
