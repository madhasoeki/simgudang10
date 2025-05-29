<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    use LogsActivity;

    protected $table = 'stok';

    protected $fillable = [
        'barang_kode',
        'jumlah',
        'harga'
    ];

    // Relasi ke tabel barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_kode', 'kode');
    }
}
