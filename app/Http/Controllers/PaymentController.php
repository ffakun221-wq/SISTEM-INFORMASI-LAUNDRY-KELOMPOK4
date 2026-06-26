<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return view('payments.index', compact('invoices', 'search', 'paidInvoice'));
    }

    public function process(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'method' => ['required', 'in:cash,qris,transfer'],
        ]);

        if ($invoice->status === 'paid') {
            return redirect()
                ->route('payments.index')
                ->with('success', 'Invoice ini sudah lunas.');
        }

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->load('laundryOrder');

            $finalTotal = (float) ($invoice->total_amount ?? 0);

            $invoice->update([
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

            $invoice->laundryOrder?->update([
                'payment_status' => 'dibayar',
            ]);
        });

        return redirect()
            ->route('payments.index', ['paid' => $invoice->id])
            ->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }
}
