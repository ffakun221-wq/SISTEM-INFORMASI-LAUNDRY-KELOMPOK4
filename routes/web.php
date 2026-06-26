<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LaundryOrderController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PublicStatusController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Halaman publik untuk pelanggan mengecek status cucian tanpa login.
Route::get('/cek-status', [PublicStatusController::class, 'index'])->name('status.check');
Route::post('/cek-status', [PublicStatusController::class, 'search'])->name('status.search');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');

// Register pelanggan dan portal pelanggan dinonaktifkan agar sistem lebih sederhana untuk presentasi.
Route::get('/register', function () {
    return redirect()->route('login');
})->name('register');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin,kasir'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class);

    Route::resource('orders', LaundryOrderController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::patch('/tracking/{order}', [TrackingController::class, 'updateStatus'])->name('tracking.update');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{invoice}/process', [PaymentController::class, 'process'])->name('payments.process');

    Route::resource('services', ServiceController::class)->except(['create', 'show', 'edit']);
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
