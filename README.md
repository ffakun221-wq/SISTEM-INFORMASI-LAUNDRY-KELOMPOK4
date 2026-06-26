# Versi Clean Source

Versi ini menghapus source code fitur yang tidak dipakai pada presentasi:
- portal pelanggan login,
- antar jemput,
- konfigurasi sistem,
- poin reward,
- notifikasi WhatsApp,
- migration dan model pendukung fitur tersebut.

Fitur aktif:
- login admin/kasir,
- dashboard,
- manajemen pelanggan,
- manajemen layanan,
- transaksi laundry,
- tracking order,
- pembayaran,
- cetak nota,
- laporan keuangan,
- cek status cucian tanpa login menggunakan kode invoice.

Setelah memakai versi ini, jalankan:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan serve
```
