# Sistem Informasi Manajemen Gudang (SIM Gudang)

Sistem Informasi Manajemen Gudang (SIM Gudang) adalah aplikasi web berbasis Laravel yang dirancang untuk membantu mengelola inventaris barang di dalam gudang. Aplikasi ini mencakup fungsionalitas untuk mengelola data barang, mencatat barang masuk, mencatat barang keluar, dan mengelola lokasi penyimpanan.

## Fitur Utama

-   **Manajemen Barang:** Tambah, lihat, edit, dan hapus data master barang.
-   **Pencatatan Barang Masuk:** Catat setiap barang yang masuk ke gudang untuk memperbarui stok secara otomatis.
-   **Pencatatan Barang Keluar:** Catat setiap barang yang keluar dari gudang untuk mengurangi stok secara otomatis.
-   **Manajemen Stok:** Lihat jumlah stok terkini untuk setiap barang.
-   **Laporan Stok Opname:** Hasilkan laporan untuk proses stok opname.
-   **Manajemen Pengguna & Hak Akses:** Sistem peran dan izin untuk membatasi akses pengguna.

## Teknologi yang Digunakan

-   **Backend:** PHP, Laravel Framework
-   **Frontend:** Blade Templates, AdminLTE 3
-   **Database:** MySQL (atau database lain yang didukung oleh Laravel)

## Prasyarat

-   PHP >= 8.1
-   Composer
-   Node.js & NPM
-   Database Server (cth: MySQL, MariaDB)

## Panduan Instalasi

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/madhasoeki/simgudang10.git](https://github.com/madhasoeki/simgudang10.git)
    cd simgudang10
    ```

2.  **Instal Dependensi PHP**
    ```bash
    composer install
    ```

3.  **Instal Dependensi JavaScript**
    ```bash
    npm install
    npm run build
    ```

4.  **Konfigurasi Lingkungan**
    * Salin file `.env.example` menjadi `.env`.
        ```bash
        cp .env.example .env
        ```
    * Buka file `.env` dan konfigurasikan koneksi database Anda (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5.  **Generate Kunci Aplikasi**
    ```bash
    php artisan key:generate
    ```

6.  **Jalankan Migrasi dan Seeder Database**
    * Perintah ini akan membuat semua tabel yang dibutuhkan dan mengisi data awal (termasuk peran dan pengguna default).
    ```bash
    php artisan migrate --seed
    ```

7.  **Jalankan Server Pengembangan**
    ```bash
    php artisan serve
    ```

    Aplikasi sekarang akan berjalan di `http://127.0.0.1:8000`.

## Pengguna Default

Setelah menjalankan *seeder*, Anda dapat login menggunakan akun berikut:

-   **Email:** `superadmin@example.com`
-   **Password:** `password`

---