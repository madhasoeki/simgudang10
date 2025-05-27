<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opname extends Model
{
    protected $table = 'opname';
    
    protected $fillable = [
        'barang_kode',
        'periode_awal',
        'periode_akhir',
        'stock_awal',
        'total_masuk',
        'total_keluar',
        'stock_total', // <-- TAMBAHKAN INI
        'total_lapangan',
        'selisih',
        'keterangan',
        'approved',
        'approved_at'
    ];

    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'approved' => 'boolean',
        'approved_at' => 'datetime'
    ];

    // Relasi ke tabel barang dan stok
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_kode', 'kode');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class, 'barang_kode', 'barang_kode');
    }

    // Scope untuk filter berdasarkan periode
    public function scopeInPeriode($query, $start, $end)
    {
        return $query->where('periode_awal', $start)->where('periode_akhir', $end);
    }
}
