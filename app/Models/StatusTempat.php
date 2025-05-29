<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusTempat extends Model
{
    use LogsActivity;
    
    protected $table = 'status_tempat';
    protected $fillable = [
        'tempat_id',
        'periode_awal',
        'periode_akhir',
        'status',
        'total'
    ];

    // Relasi ke tempat (satu status dimiliki oleh satu tempat)
    public function tempat()
    {
        return $this->belongsTo(Tempat::class, 'tempat_id');
    }

    // Scope untuk filter berdasarkan periode
    public function scopeInPeriode($query, $start, $end)
    {
        return $query->where('periode_awal', $start)->where('periode_akhir', $end);
    }
}
