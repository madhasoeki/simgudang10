<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stok;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // 1. Data untuk Info Box
        $barangMasukHariIni = BarangMasuk::whereDate('tanggal', Carbon::today())->sum('qty');
        $barangKeluarHariIni = BarangKeluar::whereDate('tanggal', Carbon::today())->sum('qty');
        $stokSubquery = DB::table('stok')
            ->select('barang_kode', DB::raw('SUM(jumlah) as total_stok'))
            ->groupBy('barang_kode');

        // Query utama dari tabel 'barangs'
        $stokMenipis = Barang::query()
            // Gabungkan tabel barang dengan hasil subquery (total stok)
            ->leftJoinSub($stokSubquery, 'stok_agregat', function ($join) {
                $join->on('barang.kode', '=', 'stok_agregat.barang_kode');
            })
            // Filter di mana total stoknya kurang dari 5.
            // COALESCE digunakan untuk mengubah NULL (barang tanpa stok) menjadi 0.
            ->where(DB::raw('COALESCE(stok_agregat.total_stok, 0)'), '<', 5)
            ->count();

        // 2. Data untuk Tabel Stok Utama
        $barangs = Barang::withSum('stok', 'jumlah')
                       ->orderBy('kode', 'asc')
                       ->get();

        // 3. Data untuk Tabel Transaksi Harian
        $transaksiMasukHariIni = BarangMasuk::with('barang')->whereDate('tanggal', Carbon::today())->latest()->get();
        $transaksiKeluarHariIni = BarangKeluar::with('barang')->whereDate('tanggal', Carbon::today())->latest()->get();

        // Kirim semua data ke view
        return view('welcome', [
            'barangMasukHariIni' => $barangMasukHariIni,
            'barangKeluarHariIni' => $barangKeluarHariIni,
            'stokMenipis' => $stokMenipis,
            'barangs' => $barangs,
            'transaksiMasukHariIni' => $transaksiMasukHariIni,
            'transaksiKeluarHariIni' => $transaksiKeluarHariIni,
        ]);
    }
}