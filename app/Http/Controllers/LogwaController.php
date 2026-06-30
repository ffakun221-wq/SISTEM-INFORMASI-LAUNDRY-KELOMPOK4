<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LogwaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $cleanSearch = preg_replace('/[^0-9]/', '', $search ?? '');

        $logs = NotificationLog::with(['customer.user'])
            ->when($search, function ($query) use ($search, $cleanSearch) {
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->whereHas('customer.user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });

                    if ($cleanSearch !== '') {
                        $q->orWhere('laundry_order_id', (int) $cleanSearch);
                    }
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'logwa_page')
            ->appends(request()->query());

        return view('settings.logwa', compact('logs'));
    }
}
