<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use SoftDeletes;

    protected $table = 'barang';
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode',
        'nama',
        'satuan'
    ];

    // Relasi ke tabel stok, opname, transaksi masuk, dan transaksi keluar
    public function stok()
    {
        return $this->hasOne(Stok::class, 'barang_kode', 'kode');
    }

    public function opnames()
    {
        return $this->hasMany(Opname::class, 'barang_kode', 'kode');
    }

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class, 'barang_kode', 'kode');
    }

    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class, 'barang_kode', 'kode');
    }
}