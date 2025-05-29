<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BarangKeluar;
use App\Models\Tempat;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables; // Pastikan ini di-import
use Illuminate\Support\Facades\Log;

class LaporanPerTempatController extends Controller
{
    public function index()
    {
        $tempats = Tempat::orderBy('nama')->get();
        return view('laporan.per_tempat', compact('tempats'));
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            // ATURAN BARU: Hanya proses jika tempat_id dipilih dan tidak kosong
            if (!$request->filled('tempat_id') || empty($request->tempat_id)) {
                return Datatables::of(collect([])) // Kirim koleksi kosong jika tempat belum dipilih
                    ->addIndexColumn()
                    // Tambahkan kolom kosong agar struktur DataTables tetap sama
                    ->addColumn('kode_barang', function ($row) { return ''; })
                    ->editColumn('tanggal', function ($row) { return ''; }) // Menggunakan 'tanggal'
                    ->addColumn('nama_barang', function ($row) { return ''; })
                    ->addColumn('satuan_barang', function ($row) { return ''; })
                    ->editColumn('harga', function ($row) { return ''; })
                    ->addColumn('jumlah_harga', function ($row) { return ''; })
                    ->addColumn('nama_tempat', function ($row) { return ''; })
                    ->addColumn('qty', function ($row) { return ''; }) // Tambahkan qty
                    ->addColumn('keterangan', function ($row) { return ''; }) // Tambahkan keterangan
                    ->rawColumns(['harga', 'jumlah_harga'])
                    ->make(true);
            }

            $query = BarangKeluar::with(['barang', 'tempat'])
                ->select('barang_keluar.*')
                ->where('tempat_id', $request->tempat_id); // Filter utama berdasarkan tempat_id

            // Filter berdasarkan periode tanggal keluar
            // Untuk laporan barang keluar, kita gunakan tanggal transaksi langsung, bukan periode opname
            if ($request->filled('start_date') && $request->filled('end_date')) {
                try {
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    // Menggunakan kolom 'tanggal' sesuai informasimu
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                } catch (\Exception $e) {
                    Log::error('Error parsing date for LaporanPerTempat: ' . $e->getMessage());
                    // Mungkin kembalikan error atau data kosong jika tanggal tidak valid
                     return Datatables::of(collect([]))->addIndexColumn()->make(true);
                }
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('kode_barang', function ($row) {
                    return $row->barang->kode ?? '-';
                })
                ->editColumn('tanggal', function ($row) { // Menggunakan 'tanggal' dari barang_keluar
                    return Carbon::parse($row->tanggal)->format('d M Y');
                })
                ->addColumn('nama_barang', function ($row) {
                    return $row->barang->nama ?? '-';
                })
                ->addColumn('satuan_barang', function ($row) {
                    return $row->barang->satuan ?? '-';
                })
                ->editColumn('harga', function ($row) {
                    return 'Rp ' . number_format($row->harga, 0, ',', '.');
                })
                ->addColumn('jumlah_harga', function ($row) {
                    return 'Rp ' . number_format($row->qty * $row->harga, 0, ',', '.');
                })
                ->addColumn('nama_tempat', function ($row) {
                    return $row->tempat->nama ?? '-';
                })
                // Kolom 'qty' dan 'keterangan' sudah ada di $row (BarangKeluar model)
                // jadi tidak perlu addColumn khusus jika namanya sama persis
                ->rawColumns(['harga', 'jumlah_harga'])
                ->make(true);
        }
    }
}