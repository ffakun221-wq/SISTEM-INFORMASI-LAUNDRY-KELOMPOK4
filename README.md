<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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
