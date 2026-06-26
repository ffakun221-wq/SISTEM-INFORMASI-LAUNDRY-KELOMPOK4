<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $services = Service::when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->get();

        return view('services.index', compact('services', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:kiloan,satuan',
            'price' => 'required|numeric|min:0',
            'estimated_hours' => 'required|integer|min:1',
        ]);

        Service::create([
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'estimated_hours' => $request->estimated_hours,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return back()->with('success', 'Layanan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:kiloan,satuan',
            'price' => 'required|numeric|min:0',
            'estimated_hours' => 'required|integer|min:1',
        ]);

        $service->update([
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'estimated_hours' => $request->estimated_hours,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return back()->with('success', 'Data layanan berhasil diperbarui.');
    }

    public function destroy(Service $service)
    {
        try {
            $service->delete();
            return back()->with('success', 'Layanan berhasil dihapus secara permanen.');
        } catch (\Illuminate\Database\QueryException $e) {
            
            return back()->with('error', 'Layanan ini tidak bisa dihapus karena sudah ada riwayat transaksi yang menggunakannya. Solusi: Cukup matikan status aktifnya (Nonaktifkan) melalui tombol Edit.');
        }
    }
}
