<?php

namespace App\Http\Controllers;

use App\Models\BarangKeluar;
use App\Models\Stok;
use App\Models\Barang;
use App\Models\Tempat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth; // Untuk user_id

class BarangKeluarController extends Controller
{
    public function index()
    {
        return view('catat-barang.barang-keluar.index');
    }

    public function data(Request $request)
    {
        $query = DB::table('barang_keluar')
            ->join('barang', 'barang_keluar.barang_kode', '=', 'barang.kode')
            ->join('tempat', 'barang_keluar.tempat_id', '=', 'tempat.id')
            ->join('users', 'barang_keluar.user_id', '=', 'users.id')
            ->select([
                'barang_keluar.id',
                'barang_keluar.tanggal',
                'barang.kode as kode_barang',
                'barang.nama as nama_barang',
                'barang_keluar.qty',
                'barang_keluar.harga',
                'barang_keluar.jumlah',
                'tempat.nama as nama_tempat',
                'barang_keluar.keterangan'
            ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
            $query->whereBetween('barang_keluar.tanggal', [$startDate, $endDate]);
        }

        $query->orderBy('barang_keluar.tanggal', 'desc')
              ->orderBy('barang_keluar.id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($row) {
                return Carbon::parse($row->tanggal)->isoFormat('DD MMMM YYYY');
            })
            ->editColumn('harga', function ($row) {
                return 'Rp ' . number_format($row->harga, 0, ',', '.');
            })
            ->editColumn('jumlah', function ($row) {
                return 'Rp ' . number_format($row->jumlah, 0, ',', '.');
            })
            ->addColumn('action', function($row){
                $editUrl = route('barang-keluar.edit', $row->id);
                $btnEdit = '<a href="'.$editUrl.'" class="btn btn-sm btn-warning mr-1"><i class="fas fa-edit"></i> Edit</a>';

                $deleteFormId = 'delete-form-bk-' . $row->id;
                $deleteUrl = route('barang-keluar.destroy', $row->id);
                
                $btnDelete = '<form id="'.$deleteFormId.'" action="'.$deleteUrl.'" method="POST" style="display:inline;">
                                '.csrf_field().'
                                '.method_field("DELETE").'
                                <button type="submit" class="btn btn-sm btn-danger delete-btn-bk" data-form-id="'.$deleteFormId.'"><i class="fas fa-trash"></i> Hapus</button>
                            </form>';

                return '<div class="btn-group">'.$btnEdit . $btnDelete.'</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $tempats = Tempat::orderBy('nama', 'asc')->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d');
        return view('catat-barang.barang-keluar.create', compact('barangs', 'tempats', 'today'));
    }

    public function getHargaStokTersedia($barang_kode)
    {
        // Ambil data stok untuk barang_kode tertentu yang jumlahnya > 0
        $stokTersedia = Stok::where('barang_kode', $barang_kode)
                            ->where('jumlah', '>', 0)
                            ->orderBy('harga', 'asc') 
                            ->select('harga', 'jumlah')
                            ->get();

        return response()->json($stokTersedia);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'harga' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:1',
            'tempat_id' => 'required|integer|exists:tempat,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string',
        ]);

        $stokTersedia = Stok::where('barang_kode', $request->barang_kode)
                            ->where('harga', $request->harga)
                            ->first();

        if (!$stokTersedia || $stokTersedia->jumlah < $request->qty) {
            return back()->withErrors([
                'qty' => 'Stok untuk barang dengan harga yang dipilih tidak mencukupi. Stok tersedia: ' . ($stokTersedia->jumlah ?? 0)
            ])->withInput();
        }

        DB::transaction(function () use ($request, $stokTersedia) {
            // Buat record baru di tabel barang_keluar
            BarangKeluar::create([
                'barang_kode' => $request->barang_kode,
                'tempat_id' => $request->tempat_id,
                'user_id' => Auth::id(),
                'tanggal' => $request->tanggal,
                'qty' => $request->qty,
                'harga' => $request->harga,
                'jumlah' => $request->qty * $request->harga,
                'keterangan' => $request->keterangan,
            ]);

            // Kurangi stok di tabel stok
            $stokTersedia->jumlah -= $request->qty;
             if ($stokTersedia->jumlah < 0) $stokTersedia->jumlah = 0;
            $stokTersedia->save();
        });

        return redirect()->route('barang-keluar.index')
                         ->with('success', 'Data barang keluar berhasil dicatat.');
    }

    public function edit(BarangKeluar $barangKeluar)
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $tempats = Tempat::orderBy('nama', 'asc')->get();
        $stokHargaTersedia = Stok::where('barang_kode', $barangKeluar->barang_kode)
                                    ->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d');
        return view('catat-barang.barang-keluar.edit', compact(
            'barangKeluar', 
            'barangs', 
            'tempats',
            'stokHargaTersedia',
            'today'
        ));
    }

    public function update(Request $request, BarangKeluar $barangKeluar)
    {
        $validatedData = $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'harga' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:1',
            'tempat_id' => 'required|integer|exists:tempat,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string',
        ]);

        $stokUntukHargaBaru = Stok::where('barang_kode', $request->barang_kode)
                                    ->where('harga', $request->harga)
                                    ->first();

        $qtyDiminta = (int)$request->qty;
        $stokTersediaUntukKombinasiBaru = $stokUntukHargaBaru ? $stokUntukHargaBaru->jumlah : 0;

        // Jika barang_kode atau harga diubah, maka qty lama harus dikembalikan dulu ke stok lama
        // dan qty baru diambil dari stok baru.
        // Jika barang_kode dan harga tidak berubah, maka hanya perlu menghitung selisih qty.
        $qtyNetChange = $qtyDiminta;
        if ($barangKeluar->barang_kode == $request->barang_kode && $barangKeluar->harga == $request->harga) {
            // Barang dan harga tidak berubah, hitung selisihnya
            $qtyNetChange = $qtyDiminta - $barangKeluar->qty;
            // Jika qtyNetChange positif, berarti mengambil lebih banyak dari sebelumnya
            // Jika negatif, berarti mengambil lebih sedikit (mengembalikan ke stok)
        }
        
        // Stok yang dibutuhkan untuk perubahan bersih (jika mengambil lebih banyak)
        // atau stok yang dibutuhkan untuk qty baru (jika barang/harga berubah)
        $stokDibutuhkan = ($barangKeluar->barang_kode == $request->barang_kode && $barangKeluar->harga == $request->harga)
                        ? ($qtyNetChange > 0 ? $qtyNetChange : 0) // Hanya butuh cek stok jika mengambil lebih banyak
                        : $qtyDiminta; // Jika barang/harga beda, cek stok penuh untuk item baru

        if ($stokDibutuhkan > 0 && $stokTersediaUntukKombinasiBaru < $stokDibutuhkan) {
            return back()->withErrors([
                'qty' => 'Stok untuk barang dengan harga baru yang dipilih tidak mencukupi. Stok tersedia: ' . $stokTersediaUntukKombinasiBaru . ', dibutuhkan tambahan: ' . $stokDibutuhkan
            ])->withInput();
        }

        DB::transaction(function () use ($request, $barangKeluar, $stokUntukHargaBaru, $qtyDiminta) {
            
            // Simpan data LAMA dari barangKeluar sebelum diupdate
            $oldBarangKode = $barangKeluar->barang_kode;
            $oldHarga = $barangKeluar->harga;
            $oldQty = $barangKeluar->qty;

            // Kembalikan stok LAMA
            // Cari stok lama berdasarkan barang_kode dan harga lama
            $stokLama = Stok::where('barang_kode', $oldBarangKode)
                            ->where('harga', $oldHarga)
                            ->first();
            
            if ($stokLama) {
                $stokLama->jumlah += $oldQty; // Tambahkan kembali qty lama ke stok
                $stokLama->save();
            } else {
                // Fallback buat record stok baru dengan qty lama (dikembalikan)
                Stok::create([
                    'barang_kode' => $oldBarangKode,
                    'harga' => $oldHarga,
                    'jumlah' => $oldQty
                ]);
            }

            // Update record barang_keluar dengan data baru
            $barangKeluar->update([
                'barang_kode' => $request->barang_kode,
                'tempat_id' => $request->tempat_id,
                'tanggal' => $request->tanggal,
                'qty' => $qtyDiminta,
                'harga' => $request->harga,
                'jumlah' => $qtyDiminta * $request->harga, // Hitung ulang total jumlah
                'keterangan' => $request->keterangan,
            ]);

            // Kurangi stok BARU berdasarkan data yang baru diinput
            $stokTargetPengurangan = Stok::where('barang_kode', $request->barang_kode)
                                        ->where('harga', $request->harga)
                                        ->first();
            
            if ($stokTargetPengurangan) {
                $stokTargetPengurangan->jumlah -= $qtyDiminta;
                if ($stokTargetPengurangan->jumlah < 0) $stokTargetPengurangan->jumlah = 0; 
                $stokTargetPengurangan->save();
            } 
        });

        return redirect()->route('barang-keluar.index')
                        ->with('success', 'Data barang keluar berhasil diperbarui.');
    }

    public function destroy(BarangKeluar $barangKeluar)
    {
        DB::transaction(function () use ($barangKeluar) {
            // Simpan informasi dari barangKeluar yang akan dihapus
            $barangKodeDihapus = $barangKeluar->barang_kode;
            $hargaDihapus = $barangKeluar->harga;
            $qtyDihapus = $barangKeluar->qty;

            // Hapus record dari tabel barang_keluar
            $barangKeluar->delete();

            // Kembalikan (tambah) stok ke tabel stok
            $stokTerkait = Stok::where('barang_kode', $barangKodeDihapus)
                                ->where('harga', $hargaDihapus)
                                ->first();

            if ($stokTerkait) {
                // Jika record stok ditemukan, tambahkan kembali kuantitasnya
                $stokTerkait->jumlah += $qtyDihapus;
                $stokTerkait->save();
            } else {
                // Jika stok tidak ditemukan, buat record baru untuk mengembalikan stok.
                Stok::create([
                    'barang_kode' => $barangKodeDihapus,
                    'harga' => $hargaDihapus,
                    'jumlah' => $qtyDihapus, // Stoknya adalah qty yang dikembalikan
                ]);
            }
        });

        // Redirect dengan pesan sukses
        return redirect()->route('barang-keluar.index')
                        ->with('success', 'Data barang keluar berhasil dihapus dan stok telah dikembalikan.');
    }
}