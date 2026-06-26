<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class PublicStatusController extends Controller
{
    public function index()
    {
        return view('public.status-check', [
            'invoice' => null,
            'searched' => false,
            'invoiceCode' => null,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'invoice_code' => ['required', 'string', 'max:100'],
        ], [
            'invoice_code.required' => 'Kode invoice wajib diisi.',
        ]);

        $invoiceCode = trim($validated['invoice_code']);

        $invoice = Invoice::with([
                'laundryOrder.customer.user',
                'laundryOrder.service',
                'laundryOrder.statusHistories' => function ($query) {
                    $query->oldest();
                },
            ])
            ->where('invoice_code', $invoiceCode)
            ->first();

        return view('public.status-check', [
            'invoice' => $invoice,
            'searched' => true,
            'invoiceCode' => $invoiceCode,
        ]);
    }
}
