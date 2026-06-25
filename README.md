<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Cara Instalasi
**1. Clone Repository (Opsi 1)**
```bash
git clone https://github.com/bilxdi/pemweb2-kasirku-H1D024074.git
```
Pastikan saat memasukkan command diatas berada di terminal folder seperti `www`(Laragon), `Herd`(Herd), dan `htdocs`(XAMPP). Karena setelah menjalankan command akan membuat satu folder baru yang berisi seluruh aplikasinya

**1. Ekstrak Proyek (Opsi 2)**<br>
Download dari github kemudian ekstrak zip dan buka terminal dari folder yang sudah di ekstrak, jangan lupa letakkan di folder yang seharusnya jika menggunakan Herd atau Laragon

**2. Install Dependencies (Composer & NPM)**
```bash
cd pemweb2-kasirku-H1D024074
```
Masuk ke terminal di dalam folder aplikasi
```bash
composer install
npm install
npm run build
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

**6. Tautkan Storage**
```bash
php artisan storage:link
```
Hubungkan ke `storage`

**7. Jalankan Server Lokal (Jika perlu)**
```bash
composer run dev
```
Jalankan aplikasi di http://pemweb2-kasirku-H1D024074.test (default herd)

## Kredensial Default (Seeder)
| Email              | Password     | Name        |
|--------------------|--------------|-------------|
| kasir@gmail.com    | 123123123    | Kasir       |
