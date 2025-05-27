<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stok';
    
    protected $primaryKey = 'barang_kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'barang_kode',
        'jumlah'
    ];

    // Relasi ke tabel barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_kode', 'kode');
    }
}
