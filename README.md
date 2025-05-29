# Sistem Informasi Manajemen Gudang (SIM Gudang)

Sistem Informasi Manajemen Gudang (SIM Gudang) adalah aplikasi web berbasis Laravel yang dirancang untuk membantu mengelola inventaris barang di dalam gudang. Aplikasi ini mencakup fungsionalitas untuk mengelola data barang, mencatat barang masuk, mencatat barang keluar, dan mengelola lokasi penyimpanan.

## Fitur Utama

- **Dashboard Interaktif**: Menampilkan ringkasan data penting seperti total barang masuk/keluar hari ini dan daftar barang dengan stok menipis.
- **Manajemen Data Master**:
    - CRUD (Create, Read, Update, Delete) untuk data **Barang**.
    - CRUD untuk data **Lokasi/Tempat** penyimpanan di gudang.
- **Pencatatan Transaksi**:
    - Pencatatan **Barang Masuk** yang secara otomatis akan menambah stok.
    - Pencatatan **Barang Keluar** yang secara otomatis akan mengurangi stok.
- **Stok Opname**: Fitur untuk melakukan penyesuaian stok fisik dengan data di sistem.
- **Manajemen Pengguna & Hak Akses**:
    - Sistem Role & Permission (menggunakan `spatie/laravel-permission`).
    - Role default: `super-admin` dan `gudang`.
    - `super-admin` memiliki akses penuh termasuk kelola pengguna dan melihat riwayat.
- **Laporan Lengkap**:
    - Laporan Stok per Lokasi/Tempat.
    - Laporan Rekapitulasi Status Tempat.
    - Laporan Hasil Stok Opname.
    - Laporan Data yang Tidak Sesuai (Data Miss).
- **Log Aktivitas**: Semua perubahan data penting (Create, Update, Delete) dicatat dalam tabel *history* untuk keperluan audit.

## Teknologi yang Digunakan

- **Backend**: PHP 8.1+, [Laravel Framework 10.x](https://laravel.com/)
- **Frontend**: Blade, Bootstrap 4, JavaScript
- **UI Admin**: [AdminLTE 3](https://adminlte.io/) via [JeroenNoten/Laravel-AdminLTE](https://github.com/jeroennoten/Laravel-AdminLTE)
- **Manajemen Hak Akses**: [Spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- **Database**: Kompatibel dengan MySQL, MariaDB, PostgreSQL
- **Build Tool**: [Vite](https://vitejs.dev/)

## Prasyarat

-   PHP >= 8.1
-   Composer
-   Node.js & NPM
-   Database Server (cth: MySQL, MariaDB)

### Langkah-langkah Instalasi

1.  **Clone repositori ini:**
    ```bash
    git clone [URL_REPOSITORY_ANDA]
    cd [NAMA_FOLDER_PROYEK]
    ```

2.  **Instal dependensi PHP via Composer:**
    ```bash
    composer install
    ```

3.  **Instal dependensi JavaScript via NPM:**
    ```bash
    npm install
    ```

4.  **Konfigurasi Lingkungan:**
    - Salin file `.env.example` menjadi `.env`.
      ```bash
      cp .env.example .env
      ```
    - Buat *application key* baru.
      ```bash
      php artisan key:generate
      ```

5.  **Konfigurasi Database:**
    - Buka file `.env` dan sesuaikan konfigurasi database Anda (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
      ```dotenv
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1
      DB_PORT=3306
      DB_DATABASE=simgudang
      DB_USERNAME=root
      DB_PASSWORD=
      ```

6.  **Jalankan Migrasi dan Seeder:**
    - Perintah ini akan membuat semua tabel yang dibutuhkan sekaligus mengisi data awal (role, permission, dan user default).
      ```bash
      php artisan migrate --seed
      ```

7.  **Kompilasi Aset Frontend:**
    ```bash
    npm run build
    ```

8.  **Jalankan Server Lokal:**
    - Anda bisa menggunakan server bawaan Laravel.
      ```bash
      php artisan serve
      ```
    - Aplikasi akan berjalan di `http://127.0.0.1:8000`.

## Akun Default

Setelah menjalankan *seeder*, sebuah akun `super-admin` akan dibuat secara otomatis. Gunakan kredensial berikut untuk login:

- **Email**: `superadmin@example.com`
- **Password**: `password`