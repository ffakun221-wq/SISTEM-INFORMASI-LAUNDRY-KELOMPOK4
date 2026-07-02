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

## Dokumentasi
<p align="center">
  <table>
    <tr>
      <td align="center">
        <img src="docs/1.png" alt="Halaman Login"><br>
        <b>Login</b>
      </td>
      <td align="center">
        <img src="docs/2.png" alt="Halaman Register untuk Pelanggan"><br>
        <b>Register</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/3.png" alt="Halaman Dashboard Admin dan Kasir"><br>
        <b>Dashboard</b>
      </td>
      <td align="center">
        <img src="docs/4.png" alt="Halaman View Manajemen Pelanggan"><br>
        <b>Manajemen Pelanggan</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/5.png" alt="Overlay Tambah Pelanggan Baru"><br>
        <b>Tambah Pelanggan</b>
      </td>
      <td align="center">
        <img src="docs/6.png" alt="Overlay Edit Pelanggan"><br>
        <b>Edit Pelanggan</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/7.png" alt="Halaman View Manajemen Layanan"><br>
        <b>Manajemen Layanan</b>
      </td>
      <td align="center">
        <img src="docs/8.png" alt="Overlay Edit Layanan"><br>
        <b>Edit Layanan</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/9.png" alt="Halaman View Manajemen Transaksi"><br>
        <b>Manajemen Transaksi</b>
      </td>
      <td align="center">
        <img src="docs/10.png" alt="Overlay Buat Transaksi Baru"><br>
        <b>Buat Transaksi</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/11.png" alt="Halaman Order Tracking"><br>
        <b>Order Tracking</b>
      </td>
      <td align="center">
        <img src="docs/12.png" alt="Halaman View Permintaan Jemput"><br>
        <b>Permintaan Jemput</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/13.png" alt="Halaman View Permintaan Antar"><br>
        <b>Permintaan Antar</b>
      </td>
      <td align="center">
        <img src="docs/14.png" alt="Halaman View Search Pembayaran"><br>
        <b>Pembayaran</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/15.png" alt="Halaman View Pembayaran"><br>
        <b>Laporan Keuangan</b>
      </td>
      <td align="center">
        <img src="docs/16.png" alt="Halaman View Konfigurasi Sistem"><br>
        <b>Konfigurasi Sistem</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/17.png" alt="Halaman View Log Notifikasi"><br>
        <b>Log Notifikasi</b>
      </td>
      <td align="center">
        <img src="docs/18.png" alt="Overlay View Detail Pesan Notifikasi"><br>
        <b>Pesan Notifikasi</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/19.png" alt="Halaman View Dashboard Pelanggan"><br>
        <b>Dashboard Pelanggan</b>
      </td>
      <td align="center">
        <img src="docs/20.png" alt="Halaman Buat Permintaan Jemput Pesanan"><br>
        <b>Buat Permintaan Jemput</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/21.png" alt="Halaman View Pesanan Aktif"><br>
        <b>Pesanan Aktif</b>
      </td>
      <td align="center">
        <img src="docs/22.png" alt="Halaman View Detail Pesanan"><br>
        <b>Detail Pesanan</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/23.png" alt="Halaman View Riwayat Pesanan"><br>
        <b>Riwayat Pesanan</b>
      </td>
      <td align="center">
        <img src="docs/24.png" alt="Halaman View Poin Pelanggan"><br>
        <b>Poin Saya</b>
      </td>
    </tr>
    <tr>
      <td align="center">
        <img src="docs/25.png" alt="Halaman Edit Akun Pelanggan"><br>
        <b>Manajemen Akun</b>
      </td>
      <td align="center">
        <img src="docs/26.png" alt="Halaman View FAQ"><br>
        <b>FAQ</b>
      </td>
    </tr>
  </table>
</p>
