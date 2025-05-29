<?php

namespace App\Http\Controllers;

use App\Models\BarangKeluar;
use App\Models\Stok; // Kita akan butuh ini nanti untuk create dan store
use App\Models\Barang; // Kita akan butuh ini nanti untuk create
use App\Models\Tempat; // Kita akan butuh ini nanti untuk create
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth; // Untuk user_id

class BarangKeluarController extends Controller
{
    /**
     * Menampilkan halaman daftar barang keluar.
     */
    public function index()
    {
        // Path view sesuai info Anda
        return view('catat-barang.barang-keluar.index');
    }

    /**
     * Menyediakan data untuk DataTables.
     */
    public function data(Request $request)
    {
        $query = DB::table('barang_keluar')
            ->join('barang', 'barang_keluar.barang_kode', '=', 'barang.kode')
            ->join('tempat', 'barang_keluar.tempat_id', '=', 'tempat.id')
            ->join('users', 'barang_keluar.user_id', '=', 'users.id')
            ->select([
                'barang_keluar.id', // Penting untuk tombol aksi
                'barang_keluar.tanggal',
                'barang.kode as kode_barang',
                'barang.nama as nama_barang', // Pastikan ini 'barang.nama'
                'barang_keluar.qty',
                'barang_keluar.harga',
                'barang_keluar.jumlah',
                'tempat.nama as nama_tempat',
                // 'users.name as user_name', // Dihapus karena diganti kolom aksi
                'barang_keluar.keterangan'
            ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
            $query->whereBetween('barang_keluar.tanggal', [$startDate, $endDate]);
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
            ->addColumn('action', function($row){
                $editUrl = route('barang-keluar.edit', $row->id);
                $btnEdit = '<a href="'.$editUrl.'" class="btn btn-xs btn-warning mr-1"><i class="fas fa-pencil-alt"></i> Edit</a>';

                $deleteFormId = 'delete-form-bk-' . $row->id; // 'bk' untuk barang keluar
                $deleteUrl = route('barang-keluar.destroy', $row->id);
                
                $btnDelete = '<form id="'.$deleteFormId.'" action="'.$deleteUrl.'" method="POST" style="display:inline;">
                                '.csrf_field().'
                                '.method_field("DELETE").'
                                <button type="submit" class="btn btn-xs btn-danger delete-btn-bk" data-form-id="'.$deleteFormId.'"><i class="fas fa-trash"></i> Hapus</button>
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
        // Kita juga mengurutkan berdasarkan harga, mungkin dari yang termurah atau termahal
        $stokTersedia = Stok::where('barang_kode', $barang_kode)
                            ->where('jumlah', '>', 0)
                            ->orderBy('harga', 'asc') // Atau 'desc' sesuai preferensi
                            ->select('harga', 'jumlah') // Hanya pilih kolom yang dibutuhkan
                            ->get();

        return response()->json($stokTersedia);
    }

    public function store(Request $request)
    {
        // 1. Validasi input dasar
        $validatedData = $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'harga' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:1',
            'tempat_id' => 'required|integer|exists:tempat,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string',
        ]);

        // 2. Validasi tambahan: Pastikan stok cukup untuk barang_kode dan harga yang dipilih
        $stokTersedia = Stok::where('barang_kode', $request->barang_kode)
                            ->where('harga', $request->harga)
                            ->first();

        if (!$stokTersedia || $stokTersedia->jumlah < $request->qty) {
            return back()->withErrors([
                'qty' => 'Stok untuk barang dengan harga yang dipilih tidak mencukupi. Stok tersedia: ' . ($stokTersedia->jumlah ?? 0)
            ])->withInput();
        }

        // 3. Lakukan operasi dalam transaksi database
        DB::transaction(function () use ($request, $stokTersedia) {
            // a. Buat record baru di tabel barang_keluar
            BarangKeluar::create([
                'barang_kode' => $request->barang_kode,
                'tempat_id' => $request->tempat_id,
                'user_id' => Auth::id(),
                'tanggal' => $request->tanggal,
                'qty' => $request->qty,
                'harga' => $request->harga,
                'jumlah' => $request->qty * $request->harga, // Hitung total jumlah
                'keterangan' => $request->keterangan,
            ]);

            // b. Kurangi stok di tabel stok
            // Kita sudah mengambil $stokTersedia di atas, jadi kita bisa langsung menggunakannya
            $stokTersedia->jumlah -= $request->qty;
            
            // Opsional: Jika Anda ingin menghapus baris stok jika jumlahnya menjadi 0
            // if ($stokTersedia->jumlah <= 0) {
            //     $stokTersedia->delete();
            // } else {
            //     $stokTersedia->save();
            // }
            // Untuk sekarang, kita hanya simpan perubahannya.
            // Pastikan tidak ada stok negatif jika itu tidak diinginkan.
             if ($stokTersedia->jumlah < 0) $stokTersedia->jumlah = 0; // Pencegahan
            $stokTersedia->save();
        });

        // 4. Redirect dengan pesan sukses
        return redirect()->route('barang-keluar.index')
                         ->with('success', 'Data barang keluar berhasil dicatat.');
    }

    public function edit(BarangKeluar $barangKeluar)
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $tempats = Tempat::orderBy('nama', 'asc')->get();
        $stokHargaTersedia = Stok::where('barang_kode', $barangKeluar->barang_kode)
                                    // ... (logika stokHargaTersedia yang sudah ada) ...
                                    ->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d');
        return view('catat-barang.barang-keluar.edit', compact(
            'barangKeluar', 
            'barangs', 
            'tempats',
            'stokHargaTersedia',
            'today' // Tambahkan today
        ));
    }

    public function update(Request $request, BarangKeluar $barangKeluar)
    {
        // 1. Validasi input dasar (mirip dengan store)
        $validatedData = $request->validate([
            'barang_kode' => 'required|string|exists:barang,kode',
            'harga' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:1',
            'tempat_id' => 'required|integer|exists:tempat,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string',
        ]);

        // 2. Validasi stok tambahan untuk data BARU yang diinput
        // Kita perlu cek apakah stok cukup jika barang/harga diubah, atau jika qty diubah
        $stokUntukHargaBaru = Stok::where('barang_kode', $request->barang_kode)
                                    ->where('harga', $request->harga)
                                    ->first();

        $qtyDiminta = (int)$request->qty;
        $stokTersediaUntukKombinasiBaru = $stokUntukHargaBaru ? $stokUntukHargaBaru->jumlah : 0;

        // Jika barang_kode atau harga diubah, maka qty lama harus dikembalikan dulu ke stok lama
        // dan qty baru diambil dari stok baru.
        // Jika barang_kode dan harga tidak berubah, maka kita hanya perlu menghitung selisih qty.
        $qtyNetChange = $qtyDiminta;
        if ($barangKeluar->barang_kode == $request->barang_kode && $barangKeluar->harga == $request->harga) {
            // Barang dan harga tidak berubah, hitung selisihnya
            $qtyNetChange = $qtyDiminta - $barangKeluar->qty;
            // Jika qtyNetChange positif, berarti kita mengambil lebih banyak dari sebelumnya
            // Jika negatif, berarti kita mengambil lebih sedikit (mengembalikan ke stok)
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


        // 3. Lakukan operasi dalam transaksi database
        DB::transaction(function () use ($request, $barangKeluar, $stokUntukHargaBaru, $qtyDiminta) {
            
            // a. Simpan data LAMA dari barangKeluar sebelum diupdate
            $oldBarangKode = $barangKeluar->barang_kode;
            $oldHarga = $barangKeluar->harga;
            $oldQty = $barangKeluar->qty;

            // b. Kembalikan stok LAMA
            // Cari stok lama berdasarkan barang_kode dan harga lama
            $stokLama = Stok::where('barang_kode', $oldBarangKode)
                            ->where('harga', $oldHarga)
                            ->first();
            
            if ($stokLama) {
                $stokLama->jumlah += $oldQty; // Tambahkan kembali qty lama ke stok
                $stokLama->save();
            } else {
                // Seharusnya ini tidak terjadi jika data konsisten,
                // tapi sebagai fallback, buat record stok baru dengan qty lama (dikembalikan)
                Stok::create([
                    'barang_kode' => $oldBarangKode,
                    'harga' => $oldHarga,
                    'jumlah' => $oldQty
                ]);
            }

            // c. Update record barang_keluar dengan data baru
            $barangKeluar->update([
                'barang_kode' => $request->barang_kode,
                'tempat_id' => $request->tempat_id,
                // user_id biasanya tidak diubah saat edit, kecuali ada kebutuhan
                'tanggal' => $request->tanggal,
                'qty' => $qtyDiminta,
                'harga' => $request->harga,
                'jumlah' => $qtyDiminta * $request->harga, // Hitung ulang total jumlah
                'keterangan' => $request->keterangan,
            ]);

            // d. Kurangi stok BARU berdasarkan data yang baru diinput
            // Kita sudah punya $stokUntukHargaBaru dari validasi, atau kita query lagi untuk keamanan
            $stokTargetPengurangan = Stok::where('barang_kode', $request->barang_kode)
                                        ->where('harga', $request->harga)
                                        ->first();
            
            // Seharusnya $stokTargetPengurangan ada karena sudah divalidasi ketersediaannya,
            // atau jika belum ada (kasus aneh), maka validasi di atas akan gagal.
            // Namun untuk lebih aman:
            if ($stokTargetPengurangan) {
                $stokTargetPengurangan->jumlah -= $qtyDiminta;
                if ($stokTargetPengurangan->jumlah < 0) $stokTargetPengurangan->jumlah = 0; // Pencegahan
                $stokTargetPengurangan->save();
            } else {
                // Ini adalah kondisi error yang seharusnya tidak tercapai jika validasi stok benar
                // Mungkin lempar exception atau tangani error karena stok tiba-tiba hilang
                // Untuk sekarang, ini akan menyebabkan error di DB jika mencoba mengurangi dari null
                // tapi validasi sebelumnya harus mencegah ini.
                // Jika kita sampai di sini, berarti ada masalah logika yang lebih dalam.
            }
        });

        // 4. Redirect dengan pesan sukses
        return redirect()->route('barang-keluar.index')
                        ->with('success', 'Data barang keluar berhasil diperbarui.');
    }

    public function destroy(BarangKeluar $barangKeluar)
    {
        // 1. Lakukan operasi dalam transaksi database
        DB::transaction(function () use ($barangKeluar) {
            // a. Simpan informasi dari barangKeluar yang akan dihapus
            $barangKodeDihapus = $barangKeluar->barang_kode;
            $hargaDihapus = $barangKeluar->harga;
            $qtyDihapus = $barangKeluar->qty;

            // b. Hapus record dari tabel barang_keluar
            $barangKeluar->delete();

            // c. Kembalikan (tambah) stok ke tabel stok
            //    Cari record stok yang sesuai dengan barang_kode dan harga dari transaksi yang dihapus
            $stokTerkait = Stok::where('barang_kode', $barangKodeDihapus)
                                ->where('harga', $hargaDihapus)
                                ->first();

            if ($stokTerkait) {
                // Jika record stok ditemukan, tambahkan kembali kuantitasnya
                $stokTerkait->jumlah += $qtyDihapus;
                $stokTerkait->save();
            } else {
                // Kondisi ini idealnya tidak terjadi jika data konsisten.
                // Artinya, barang dikeluarkan dari stok yang tidak tercatat (atau record stoknya terhapus).
                // Sebagai fallback, kita bisa membuat record stok baru dengan jumlah yang dikembalikan.
                // Ini membantu menjaga data stok tetap ada meskipun mungkin ada anomali sebelumnya.
                Stok::create([
                    'barang_kode' => $barangKodeDihapus,
                    'harga' => $hargaDihapus,
                    'jumlah' => $qtyDihapus, // Stoknya adalah qty yang dikembalikan
                ]);
            }
        });

        // 2. Redirect dengan pesan sukses
        return redirect()->route('barang-keluar.index')
                        ->with('success', 'Data barang keluar berhasil dihapus dan stok telah dikembalikan.');
    }
}