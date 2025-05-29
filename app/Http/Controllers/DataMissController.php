<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opname;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class DataMissController extends Controller
{
    public function index()
    {
        // Sama seperti OpnameController, view akan diisi oleh DataTables
        return view('laporan.data_miss'); // Kita akan buat view ini nanti
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            // Ambil data dari tabel opname
            // dengan relasi ke barang untuk mendapatkan kode, nama, dan satuan
            $query = Opname::with(['barang'])
                ->where('selisih', '!=', 0) // Filter utama: hanya yang ada selisih
                ->select('opname.*');

            // Implementasi filter tanggal yang sama seperti di OpnameController
            // Ini penting agar user bisa melihat data miss untuk periode tertentu
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                
                // Filter berdasarkan periode_awal dan periode_akhir opname
                // Menampilkan semua opname yang periodenya bersinggungan dengan filter tanggal
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->where('periode_awal', '<=', $endDate)
                      ->where('periode_akhir', '>=', $startDate);
                });
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('kode_barang', function ($row) {
                    return $row->barang->kode ?? '-'; // Ambil dari relasi barang
                })
                ->addColumn('nama_barang', function ($row) {
                    return $row->barang->nama ?? '-'; // Ambil dari relasi barang
                })
                ->addColumn('satuan_barang', function ($row) {
                    return $row->barang->satuan ?? '-'; // Ambil dari relasi barang
                })
                ->addColumn('miss', function ($row) {
                    // Kolom 'miss' adalah nilai 'selisih' itu sendiri
                    // Kita bisa format warnanya juga jika mau
                    $selisih = $row->selisih;
                    $badge_class = 'secondary';
                    if ($selisih > 0) { // Lapangan lebih banyak (surplus)
                        $badge_class = 'success';
                    } elseif ($selisih < 0) { // Lapangan lebih sedikit (minus/kekurangan)
                        $badge_class = 'danger';
                    }
                    return '<span class="badge badge-'.$badge_class.'">'.$selisih.'</span>';
                })
                // Kolom 'keterangan' sudah ada di $row->keterangan
                ->rawColumns(['miss']) // Kolom yang berisi HTML perlu di-render
                ->make(true);
        }
    }
}