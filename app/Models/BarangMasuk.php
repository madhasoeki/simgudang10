<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    use LogsActivity;
    
    protected $table = 'barang_masuk';

    // Mass assignment protection
    protected $fillable = ['tanggal', 'barang_kode', 'qty', 'harga', 'jumlah', 'user_id'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_kode', 'kode');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Otomatis set jumlah jika tidak diisi
            if (!$model->jumlah) {
                $model->jumlah = $model->qty * $model->harga;
            }
        });
    }
}
