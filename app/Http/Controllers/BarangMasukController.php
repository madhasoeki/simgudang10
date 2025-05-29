<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stok;
use App\Models\Barang;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <-- Tambahkan untuk mengambil user login
use Yajra\DataTables\Facades\DataTables;

class BarangMasukController extends Controller
{
    public function index()
    {
        // Pastikan path view Anda benar. Sesuaikan jika perlu.
        return view('catat-barang.barang-masuk.index');
    }

    // app/Http/Controllers/BarangMasukController.php

    public function data(Request $request)
    {
        $query = DB::table('barang_masuk')
            ->join('barang', 'barang_masuk.barang_kode', '=', 'barang.kode')
            ->join('users', 'barang_masuk.user_id', '=', 'users.id')
            ->select([
                'barang_masuk.id', // Pastikan ID barang_masuk ada untuk tombol aksi
                'barang_masuk.tanggal',
                'barang_masuk.qty',
                'barang_masuk.harga',
                'barang_masuk.jumlah',
                'barang.nama',
                'barang.kode as kode_barang',
                'users.name as user_name'
            ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
            $query->whereBetween('barang_masuk.tanggal', [$startDate, $endDate]);
        }

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
            ->addColumn('action', function($row){ // <-- Tambahkan kolom aksi
                // Tombol Edit
                $editUrl = route('barang-masuk.edit', $row->id); // Asumsi nama rute adalah barang-masuk.edit
                $btnEdit = '<a href="'.$editUrl.'" class="btn btn-sm btn-warning mr-1"><i class="fas fa-edit"></i> Edit</a>';

                // Tombol Hapus (akan menggunakan form untuk method DELETE)
                // ID unik untuk form hapus, misalnya delete-form-1, delete-form-2, dst.
                $deleteFormId = 'delete-form-' . $row->id;
                $deleteUrl = route('barang-masuk.destroy', $row->id); // Asumsi nama rute adalah barang-masuk.destroy
                
                // Tambahkan class 'delete-btn' untuk event listener JavaScript
                $btnDelete = '<form id="'.$deleteFormId.'" action="'.$deleteUrl.'" method="POST" style="display:inline;">
                                '.csrf_field().'
                                '.method_field("DELETE").'
                                <button type="submit" class="btn btn-sm btn-danger delete-btn" data-form-id="'.$deleteFormId.'"><i class="fas fa-trash"></i> Hapus</button>
                            </form>';

                return '<div class="btn-group">'.$btnEdit . $btnDelete.'</div>';
            })
            ->rawColumns(['action']) // <-- Beritahu DataTables bahwa kolom 'action' mengandung HTML
            ->make(true);
    }

    public function create()
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d'); // Ambil tanggal hari ini
        return view('catat-barang.barang-masuk.create', compact('barangs', 'today'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'qty' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'tanggal' => 'required|date|before_or_equal:today',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Simpan data ke tabel barang_masuk (tidak ada perubahan di sini)
            BarangMasuk::create([
                'tanggal' => $request->tanggal,
                'barang_kode' => $request->barang_kode,
                'qty' => $request->qty,
                'harga' => $request->harga,
                'user_id' => Auth::id(),
            ]);

            // 2. Update atau buat data di tabel stok dengan logika baru
            $stok = Stok::firstOrNew([
                'barang_kode' => $request->barang_kode,
                'harga' => $request->harga
            ]);

            // Tambahkan qty ke jumlah yang sudah ada (jika baru, jumlah awal 0)
            $stok->jumlah = ($stok->jumlah ?? 0) + $request->qty;
            $stok->save();
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil ditambahkan.');
    }

    public function edit(BarangMasuk $barangMasuk)
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d'); // Ambil tanggal hari ini
        return view('catat-barang.barang-masuk.edit', compact('barangMasuk', 'barangs', 'today'));
    }

    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        // Validasi input sama seperti di store, atau sesuaikan jika ada field yang tidak bisa diedit
        $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'qty' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'tanggal' => 'required|date|before_or_equal:today', // <-- Validasi ditambahkan
        ]);

        DB::transaction(function () use ($request, $barangMasuk) {
            // 1. Simpan data LAMA dari barangMasuk sebelum diupdate
            $oldBarangKode = $barangMasuk->barang_kode;
            $oldHarga = $barangMasuk->harga;
            $oldQty = $barangMasuk->qty;

            // 2. Kurangi stok lama
            $stokLama = Stok::where('barang_kode', $oldBarangKode)
                            ->where('harga', $oldHarga)
                            ->first();
            if ($stokLama) {
                $stokLama->jumlah -= $oldQty;
                // Jika stok menjadi 0 atau kurang setelah dikurangi, Anda bisa memilih untuk menghapusnya
                // atau membiarkannya dengan jumlah 0 (atau bahkan negatif jika itu diizinkan).
                // Untuk contoh ini, kita biarkan saja, tapi pastikan tidak negatif jika tidak diinginkan.
                if ($stokLama->jumlah < 0) $stokLama->jumlah = 0; // Contoh pencegahan stok negatif
                $stokLama->save();
            }

            // 3. Update record barang_masuk dengan data baru
            $barangMasuk->update([
                'tanggal' => $request->tanggal,
                'barang_kode' => $request->barang_kode,
                'qty' => $request->qty,
                'harga' => $request->harga,
                // 'jumlah' akan diupdate otomatis oleh model event jika logika $fillable dan create/update mengizinkannya
                // atau Anda bisa set manual: 'jumlah' => $request->qty * $request->harga,
                // user_id biasanya tidak diubah saat edit transaksi, kecuali ada kebutuhan khusus
            ]);
            // Jika 'jumlah' tidak otomatis terupdate oleh model event saat update:
            if ($barangMasuk->wasChanged('qty') || $barangMasuk->wasChanged('harga')) {
                $barangMasuk->jumlah = $barangMasuk->qty * $barangMasuk->harga;
                $barangMasuk->saveQuietly(); // Simpan tanpa memicu event lain
            }


            // 4. Tambah/Update stok baru berdasarkan data yang baru diinput
            $stokBaru = Stok::firstOrNew([
                'barang_kode' => $request->barang_kode, // Bisa jadi kode barangnya berubah
                'harga' => $request->harga             // Bisa jadi harganya berubah
            ]);
            $stokBaru->jumlah = ($stokBaru->jumlah ?? 0) + $request->qty;
            $stokBaru->save();

        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil diperbarui.');
    }

    public function destroy(BarangMasuk $barangMasuk)
    {
        DB::transaction(function () use ($barangMasuk) {
            // 1. Ambil data yang akan dihapus untuk penyesuaian stok
            $barangKodeToDelete = $barangMasuk->barang_kode;
            $hargaToDelete = $barangMasuk->harga;
            $qtyToDelete = $barangMasuk->qty;

            // 2. Hapus record barang_masuk
            $barangMasuk->delete();

            // 3. Kurangi stok terkait
            $stok = Stok::where('barang_kode', $barangKodeToDelete)
                        ->where('harga', $hargaToDelete)
                        ->first();

            if ($stok) {
                $stok->jumlah -= $qtyToDelete;
                // Opsional: Jika stok menjadi 0, Anda bisa menghapus record stoknya
                // if ($stok->jumlah <= 0) {
                //     $stok->delete();
                // } else {
                //     $stok->save();
                // }
                // Untuk sekarang, kita biarkan saja, pastikan tidak negatif
                if ($stok->jumlah < 0) $stok->jumlah = 0;
                $stok->save();
            }
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil dihapus.');
    }
}