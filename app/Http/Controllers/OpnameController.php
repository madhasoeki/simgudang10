<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Opname;
use App\Models\Stok;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;


class OpnameController extends Controller
{
    public function index()
    {
        return view('laporan.opname');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = Opname::with(['barang'])->select('opname.*');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('periode_awal', '<=', $endDate)
                        ->where('periode_akhir', '>=', $startDate);
                });
            }

            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('total_lapangan', function ($row) {
                    $opnameId = $row->id;
                    $currentValue = $row->total_lapangan ?? 0;
                    if ($row->approved) {
                        return $currentValue;
                    }
                    // data-original-value digunakan oleh JS untuk reset jika batal edit
                    return "<div class='editable-cell' data-opname-id='{$opnameId}' data-field='total_lapangan' data-original-value='{$currentValue}'>
                                <span class='editable-text'>{$currentValue}</span>
                                <input type='number' class='form-control form-control-sm editable-input' value='{$currentValue}' style='display:none; width: 80px; text-align:right; padding-right: 2px;'>
                            </div>";
                })
                ->editColumn('keterangan', function ($row) {
                    $opnameId = $row->id;
                    $currentValue = $row->keterangan ?? '';
                    if ($row->approved) {
                        return $currentValue ?: '-';
                    }
                    return "<div class='editable-cell' data-opname-id='{$opnameId}' data-field='keterangan' data-original-value='{$currentValue}'>
                                <span class='editable-text'>" . ($currentValue ?: '-') . "</span>
                                <input type='text' class='form-control form-control-sm editable-input' value='{$currentValue}' style='display:none;'>
                            </div>";
                })
                ->editColumn('selisih', function ($row) {
                    $currentSelisih = 0; 
                    if (!is_null($row->total_lapangan)) {
                        $currentSelisih = $row->total_lapangan - $row->stock_total;
                    } else if ($row->total_lapangan === 0 && $row->stock_total != 0) {
                        // Jika lapangan belum diisi (null dari DB, atau 0 dari input) dan sistem ada stok, selisihnya adalah minus stok sistem
                         $currentSelisih = -$row->stock_total;
                    }

                    $badge_class = 'secondary';
                    if (!is_null($row->total_lapangan)) { // Hanya beri warna jika total_lapangan sudah ada (bukan null)
                        if ($currentSelisih == 0) {
                            $badge_class = 'primary'; // Netral
                        } elseif ($currentSelisih > 0) { // Lapangan lebih banyak (surplus)
                            $badge_class = 'success';
                        } else { // Lapangan lebih sedikit (minus)
                            $badge_class = 'danger';
                        }
                        return '<span class="badge badge-' . $badge_class . '">' . $currentSelisih . '</span>';
                    }
                    return '<span class="badge badge-secondary">-</span>'; // Default jika total_lapangan masih null
                })
                ->addColumn('actions', function ($row) {
                    $opnameId = $row->id;
                    $buttons = '';

                    if (!$row->approved) {
                        $buttons .= "<button type='button' class='btn btn-xs btn-info btn-edit-row mr-1' data-opname-id='{$opnameId}' title='Edit Data Lapangan & Keterangan'><i class='fa fa-pencil-alt'></i></button>";
                        $buttons .= "<button type='button' class='btn btn-xs btn-primary btn-save-row mr-1' data-opname-id='{$opnameId}' title='Simpan Perubahan' style='display:none;'><i class='fa fa-save'></i></button>";
                        $buttons .= "<button type='button' class='btn btn-xs btn-secondary btn-cancel-row mr-1' data-opname-id='{$opnameId}' title='Batal Edit' style='display:none;'><i class='fa fa-times'></i></button>";

                        if (Auth::check() && Auth::user()->can('approve opname')) {
                            $approveUrl = route('opname.approve', $opnameId);
                            $csrf = csrf_field();
                            $buttons .= "
                                <form id='form-approve-{$opnameId}' action='{$approveUrl}' method='POST' class='d-inline btn-approve-form'>
                                    {$csrf}
                                    <button type='submit' class='btn btn-success btn-sm btn-confirm-approve' data-opname-id='{$opnameId}' title='Approve Opname'><i class='fa fa-check'></i> Approve</button>
                                </form>";
                        }
                    } else {
                        return '<span class="badge badge-success">Approved at: ' . ($row->approved_at ? Carbon::parse($row->approved_at)->format('d M Y H:i') : 'N/A') . '</span>';
                    }
                    return $buttons;
                })
                ->rawColumns(['total_lapangan', 'keterangan', 'selisih', 'actions'])
                ->make(true);
        }
    }

    public function refresh()
    {
        try {
            Artisan::call('opname:generate');
            return redirect()->route('opname.index')->with('success', 'Data opname berhasil disegarkan!');
        } catch (\Exception $e) {
            Log::error('Error refreshing opname data: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect()->route('opname.index')->with('error', 'Gagal menyegarkan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Opname $opname)
    {
        $validatedData = $request->validate([
            'total_lapangan' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($opname->approved) {
            return redirect()->route('opname.index')->with('error', 'Data yang sudah diapprove tidak bisa diubah.');
        }

        $selisih = $validatedData['total_lapangan'] - $opname->stock_total; // Lapangan - Sistem

        if ($selisih != 0 && empty($validatedData['keterangan'])) {
             // Kondisi ini memastikan bahwa jika total_lapangan adalah 0 dan stock_total juga 0 (selisih 0), keterangan tidak wajib.
             // Atau jika total_lapangan bukan 0 tapi ada selisih, keterangan jadi wajib.
            if (!($validatedData['total_lapangan'] == 0 && $opname->stock_total == 0)) {
                 return redirect()->back()
                        ->withInput($request->except('keterangan'))
                        ->with('error', 'Keterangan wajib diisi jika Stok Lapangan berbeda dengan Stok Sistem untuk barang ' . $opname->barang->nama . '.');
            }
        }

        $opname->total_lapangan = $validatedData['total_lapangan'];
        $opname->keterangan = $validatedData['keterangan'];
        $opname->selisih = $selisih;
        $opname->save();

        // Jika request datang dari AJAX (untuk save inline), kembalikan JSON
        if ($request->ajax()) {
            return response()->json(['message' => 'Data opname untuk ' . $opname->barang->nama . ' berhasil disimpan.']);
        }
        return redirect()->route('opname.index')->with('success', 'Data opname untuk ' . $opname->barang->nama . ' berhasil disimpan.');
    }

    public function approve(Request $request, Opname $opname)
    {
        $validatedData = $request->validate([
            'total_lapangan' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($opname->approved) {
            return redirect()->route('opname.index')->with('error', 'Data ini sudah pernah diapprove.');
        }

        $opname->total_lapangan = $validatedData['total_lapangan'];
        $opname->keterangan = $validatedData['keterangan'];
        $opname->selisih = $validatedData['total_lapangan'] - $opname->stock_total; // Lapangan - Sistem

        if ($opname->selisih != 0 && empty($opname->keterangan)) {
            if (!($opname->total_lapangan == 0 && $opname->stock_total == 0)) {
                return redirect()->back()
                    ->withInput($request->except('keterangan'))
                    ->with('error', 'Keterangan wajib diisi jika Stok Lapangan berbeda dengan Stok Sistem untuk barang ' . $opname->barang->nama . ' sebelum approve.');
            }
        }
        $opname->save();

        if (is_null($opname->total_lapangan)) {
            return redirect()->back()->with('error', 'Data lapangan harus diisi sebelum melakukan approval untuk ' . $opname->barang->nama);
        }

        DB::beginTransaction();
        try {
            $this->adjustStock($opname);
            $opname->update([
                'approved' => true,
                'approved_at' => now(),
            ]);
            DB::commit();
            return redirect()->route('opname.index')->with('success', 'Opname untuk ' . $opname->barang->nama . ' berhasil diapprove.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving opname: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect()->route('opname.index')->with('error', 'Approval gagal: ' . $e->getMessage());
        }
    }

    private function adjustStock(Opname $opname)
    {
        $selisih = $opname->selisih; // selisih = Lapangan - Sistem
        if ($selisih == 0) return;

        $stoks = Stok::where('barang_kode', $opname->barang_kode)
            ->where('jumlah', '>', 0) // Hanya ambil stok yang ada jumlahnya untuk disesuaikan
            ->orderBy('harga', 'asc')
            ->get();


        if ($selisih > 0) { // Lapangan > Sistem, perlu MENAMBAH stok di sistem
            $this->increaseStock($opname, $selisih); 
        } else { // Lapangan < Sistem, perlu MENGURANGI stok di sistem
            $this->decreaseStock($opname, abs($selisih));
        }
    }
    
    private function decreaseStock(Opname $opname, $jumlahPengurangan) {
        $stoksToUpdate = Stok::where('barang_kode', $opname->barang_kode)
            ->where('jumlah', '>', 0)
            ->orderBy('harga', 'asc')
            ->get();

        $sisaPengurangan = $jumlahPengurangan;
        foreach($stoksToUpdate as $stok) {
            if ($sisaPengurangan <= 0) break;
            

            $jumlahDapatDikurangi = min($stok->jumlah, $sisaPengurangan);
            $stok->jumlah -= $jumlahDapatDikurangi;
            $stok->save(); 
            $sisaPengurangan -= $jumlahDapatDikurangi;

        }
    }

    private function increaseStock(Opname $opname, $jumlahPenambahan) {
        $stoksToUpdate = Stok::where('barang_kode', $opname->barang_kode)
            ->orderBy('harga', 'asc')
            ->get();

        if ($stoksToUpdate->isNotEmpty()) {
            $stokTermurah = $stoksToUpdate->first();

            $stokTermurah->jumlah += $jumlahPenambahan;
            $stokTermurah->save();
            
        } else {
            Log::warning("Tidak ada entri harga di tabel stok untuk barang_kode: {$opname->barang_kode} saat mencoba menambah stok opname.");
        }
    }
}