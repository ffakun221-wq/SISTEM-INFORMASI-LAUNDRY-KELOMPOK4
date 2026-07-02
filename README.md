<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="docs/brand1.svg">
        <img src="docs/brand2.svg" width=500 alt="Laundry System Brand">
    </picture>
</p>

## About
Sistem Informasi Laundry merupakan aplikasi berbasis web yang dirancang untuk membantu proses operasional usaha laundry secara terintegrasi. Sistem ini dikembangkan untuk menggantikan proses pencatatan manual yang selama ini digunakan oleh banyak usaha laundry sehingga pengelolaan data menjadi lebih cepat, akurat, dan efisien.

## Fitur
Sistem menyediakan 3 jenis pengguna (Admin, Kasir, dan Pelanggan) dan beberapa fitur seperti:
- Autentikasi (Login, Logout, Registrasi, Validasi Akun)
- Manajemen Pelanggan (Tambah, Ubah, Hapus, Cari Pelanggan)
- Transaksi Laundry (Pembuatan Transaksi, Pemilihan Layanan, Perhitungan Harga, Pesanan Jemput)
- Order Tracking (Pemantauan Status, Pembaruan Status, Riwayat Status, Tracking Pelanggan)
- Pembayaran dan Nota (Pelunasan, Cetak Nota, Diskon Poin, Biaya Pengantaran)
- Laporan Keuangan (Laporan Harian dan Bulanan)
- Portal Pelanggan (Dashboard, Riwayat Transaksi, Informasi Akun, Saldo Poin)
- Jemput Cucian (Pembuatan dan Konfirmasi Pesanan Jemput)
- Pengantaran Cucian (Opsi Antar, Perhitungan Biaya, Batas Waktu Antar)
- Notifikasi WhatsApp dan Log Notifikasi
- Sistem Poin dan Diskon Loyalitas

## Cara Instalasi
**1. Clone Repository (Opsi 1)**
```bash
git clone https://github.com/ffakun221-wq/SISTEM-INFORMASI-LAUNDRY-KELOMPOK4.git
```
Pastikan saat memasukkan command diatas berada di terminal folder seperti `www`(Laragon), `Herd`(Herd), dan `htdocs`(XAMPP). Karena setelah menjalankan command akan membuat satu folder baru yang berisi seluruh aplikasinya

**1. Ekstrak Proyek (Opsi 2)**<br>
Download dari github kemudian ekstrak zip dan buka terminal dari folder yang sudah di ekstrak, jangan lupa letakkan di folder yang seharusnya jika menggunakan Herd atau Laragon

**2. Install Dependencies (Composer & NPM)**
```bash
cd SISTEM-INFORMASI-LAUNDRY-KELOMPOK4
```
Masuk ke terminal di dalam folder aplikasi
```bash
composer install
```
Kemudian jalankan command diatas

**3. Konfigurasi Environment (File .env)**<br>
```bash
cp .\.env.example .env
```
Copy paste `.env.example` jadi `.env`, jangan lupa ubah `DB_DATABASE` dan `DB_CONNECTION` jika menggunakan yang lain

**4. Generate Application Key**
```bash
php artisan key:generate
```
Generate app key pada `.env`

**5. Jalankan Migrasi & Seeder**
```bash
php artisan migrate:fresh --seed
```
Jalankan migrasi dan seeding ke database

**6. Jalankan Server Lokal (Jika perlu)**
```bash
composer run dev
```
Atau
```bash
php artisan serve
```
Jalankan aplikasi di http://SISTEM-INFORMASI-LAUNDRY-KELOMPOK4.test (default herd)

## Kredensial Default (Seeder)
| Username    | Kata Sandi   | Email              |
|-------------|--------------|--------------------|
| admin       | 123123123    | kasir@gmail.com    |
| pelanggan   | 123123123    | kasir@gmail.com    |
| kasir       | 123123123    | kasir@gmail.com    |
