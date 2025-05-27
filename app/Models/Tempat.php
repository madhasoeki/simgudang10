<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tempat extends Model
{
    use SoftDeletes;

    protected $table = 'tempat';
    protected $fillable = ['nama'];

    // Relasi ke status_tempat dan barang_keluar
    public function statusTempat(): HasMany
    {
        return $this->hasMany(StatusTempat::class);
    }
    
    public function barangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class);
    }
}
