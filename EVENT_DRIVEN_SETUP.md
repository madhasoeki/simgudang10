# Event-Driven Calculation System

## Cara Kerja

Sistem ini menggunakan **Event-Driven Architecture** untuk kalkulasi otomatis:

### 1. **Barang Masuk (BarangMasuk)**
Ketika ada transaksi barang masuk (created/updated/deleted):
- ✅ **Otomatis kalkulasi Opname** untuk `barang_kode` terkait

### 2. **Barang Keluar (BarangKeluar)**
Ketika ada transaksi barang keluar (created/updated/deleted):
- ✅ **Otomatis kalkulasi Opname** untuk `barang_kode` terkait
- ✅ **Otomatis kalkulasi Status Tempat** untuk `tempat_id` terkait

## Setup Queue

### Opsi 1: Database Queue (Paling Mudah)

1. **Update `.env`:**
```env
QUEUE_CONNECTION=database
```

2. **Buat tabel jobs:**
```bash
php artisan queue:table
php artisan migrate
```

3. **Jalankan queue worker:**
```bash
php artisan queue:work --tries=3 --timeout=60
```

### Opsi 2: Redis Queue (Lebih Cepat - untuk Production)

1. **Install Redis:**
```bash
composer require predis/predis
```

2. **Update `.env`:**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. **Jalankan queue worker:**
```bash
php artisan queue:work redis --queue=calculations --tries=3 --timeout=60
```

### Opsi 3: Sync (Untuk Development/Testing)

**Update `.env`:**
```env
QUEUE_CONNECTION=sync
```

> ⚠️ **Catatan:** Mode `sync` akan menjalankan job langsung (blocking), cocok untuk testing tapi tidak untuk production.

## Menjalankan Queue Worker

### Development (Manual)
```bash
php artisan queue:work --tries=3
```

### Production (dengan Supervisor)

1. **Install Supervisor:**
```bash
sudo apt-get install supervisor
```

2. **Buat config:** `/etc/supervisor/conf.d/laravel-worker.conf`
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/simgudang10/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/simgudang10/storage/logs/worker.log
stopwaitsecs=3600
```

3. **Reload Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Testing

### 1. Test Barang Masuk
```php
// Di Tinker atau Controller
$barangMasuk = BarangMasuk::create([
    'barang_kode' => 'BRG001',
    'tanggal' => now(),
    'qty' => 10,
    'harga' => 5000,
    'user_id' => 1
]);

// Cek log: storage/logs/laravel.log
// Cek job: SELECT * FROM jobs; (jika pakai database queue)
// Cek hasil: SELECT * FROM opname WHERE barang_kode = 'BRG001';
```

### 2. Test Barang Keluar
```php
$barangKeluar = BarangKeluar::create([
    'barang_kode' => 'BRG001',
    'tempat_id' => 1,
    'tanggal' => now(),
    'qty' => 5,
    'harga' => 5000,
    'user_id' => 1
]);

// Cek log untuk 2 job:
// - CalculateOpnameJob
// - CalculateStatusTempatJob
```

## Monitoring

### Cek Job yang Pending
```bash
php artisan queue:listen --verbose
```

### Cek Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
# Retry semua
php artisan queue:retry all

# Retry job tertentu
php artisan queue:retry <job-id>
```

### Clear Failed Jobs
```bash
php artisan queue:flush
```

## Keuntungan System Ini

✅ **Real-time**: Data selalu update otomatis tanpa perlu refresh manual  
✅ **Efficient**: Hanya kalkulasi data yang berubah, bukan semua data  
✅ **Non-blocking**: Tidak memperlambat input transaksi user  
✅ **Reliable**: Ada retry mechanism (3x) jika gagal  
✅ **Scalable**: Bisa handle banyak transaksi bersamaan  
✅ **No Cron Job**: Tidak perlu setup cron scheduler  

## Manual Refresh (Fallback)

Jika diperlukan manual refresh untuk semua data:

**Opname:**
```bash
php artisan opname:generate
```

**Status Tempat:**
```bash
php artisan statustempat:generate
```

Atau melalui button di UI (sudah ada di controller).
