<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangKeluar extends Model
{
    use LogsActivity;
    
    protected $table = 'barang_keluar';
    protected $fillable = [
        'barang_kode',
        'tempat_id',
        'tanggal',
        'qty',
        'harga',
        'jumlah',
        'keterangan',
        'user_id'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // Relasi ke barang, tempat dan user
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_kode', 'kode');
    }

    public function tempat(): BelongsTo
    {
        return $this->belongsTo(Tempat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
