<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Stok;
use App\Models\Tempat;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Untuk user_id
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BarangKeluarController extends Controller
{
    private const CURRENCY_PREFIX = 'Rp ';

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
                'barang_keluar.keterangan',
            ]);

        $this->applyDateFilter($query, $request, 'barang_keluar.tanggal');

        $query->orderBy('barang_keluar.tanggal', 'desc')
            ->orderBy('barang_keluar.id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', function ($row) {
                return Carbon::parse($row->tanggal)->isoFormat('DD MMMM YYYY');
            })
            ->editColumn('harga', function ($row) {
                return $this->formatCurrency((int) $row->harga);
            })
            ->editColumn('jumlah', function ($row) {
                return $this->formatCurrency((int) $row->jumlah);
            })
            ->addColumn('action', function ($row) {
                return $this->buildActionButtons((int) $row->id);
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
        $stokTersedia = Stok::where('barang_kode', $barang_kode)
            ->where('jumlah', '>', 0)
            ->orderBy('harga', 'asc')
            ->select('harga', 'jumlah')
            ->get();

        return response()->json($stokTersedia);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->rules());
        $qty = (int) $validatedData['qty'];
        $harga = (int) $validatedData['harga'];

        $stokTersedia = $this->findStock($validatedData['barang_kode'], $harga);
        if (! $stokTersedia || (int) $stokTersedia->jumlah < $qty) {
            return back()->withErrors([
                'qty' => 'Stok untuk barang dengan harga yang dipilih tidak mencukupi. Stok tersedia: '.((int) ($stokTersedia->jumlah ?? 0)),
            ])->withInput();
        }

        DB::transaction(function () use ($validatedData, $harga, $qty) {
            BarangKeluar::create([
                'barang_kode' => $validatedData['barang_kode'],
                'tempat_id' => $validatedData['tempat_id'],
                'user_id' => Auth::id(),
                'tanggal' => $validatedData['tanggal'],
                'qty' => $qty,
                'harga' => $harga,
                'jumlah' => $qty * $harga,
                'keterangan' => $validatedData['keterangan'] ?? null,
            ]);

            $this->adjustStock($validatedData['barang_kode'], $harga, -$qty);
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
        $validatedData = $request->validate($this->rules());

        $newBarangKode = $validatedData['barang_kode'];
        $newHarga = (int) $validatedData['harga'];
        $newQty = (int) $validatedData['qty'];

        $oldBarangKode = $barangKeluar->barang_kode;
        $oldHarga = (int) $barangKeluar->harga;
        $oldQty = (int) $barangKeluar->qty;

        $isSameStockBucket = $oldBarangKode === $newBarangKode && $oldHarga === $newHarga;
        $targetStock = $this->findStock($newBarangKode, $newHarga);

        if ($isSameStockBucket) {
            $availableAfterRollback = (int) ($targetStock->jumlah ?? 0) + $oldQty;
            if ($availableAfterRollback < $newQty) {
                return back()->withErrors([
                    'qty' => 'Stok untuk barang dengan harga yang dipilih tidak mencukupi. Stok tersedia setelah rollback transaksi lama: '.$availableAfterRollback,
                ])->withInput();
            }
        } elseif ((int) ($targetStock->jumlah ?? 0) < $newQty) {
            return back()->withErrors([
                'qty' => 'Stok untuk barang dengan harga baru yang dipilih tidak mencukupi. Stok tersedia: '.((int) ($targetStock->jumlah ?? 0)).', dibutuhkan: '.$newQty,
            ])->withInput();
        }

        DB::transaction(function () use ($validatedData, $barangKeluar, $oldBarangKode, $oldHarga, $oldQty, $newQty, $newHarga) {
            $this->adjustStock($oldBarangKode, $oldHarga, $oldQty);

            $barangKeluar->update([
                'barang_kode' => $validatedData['barang_kode'],
                'tempat_id' => $validatedData['tempat_id'],
                'tanggal' => $validatedData['tanggal'],
                'qty' => $newQty,
                'harga' => $newHarga,
                'jumlah' => $newQty * $newHarga,
                'keterangan' => $validatedData['keterangan'] ?? null,
            ]);

            $this->adjustStock($validatedData['barang_kode'], $newHarga, -$newQty);
        });

        return redirect()->route('barang-keluar.index')
            ->with('success', 'Data barang keluar berhasil diperbarui.');
    }

    public function destroy(BarangKeluar $barangKeluar)
    {
        DB::transaction(function () use ($barangKeluar) {
            $barangKodeDihapus = $barangKeluar->barang_kode;
            $hargaDihapus = (int) $barangKeluar->harga;
            $qtyDihapus = (int) $barangKeluar->qty;

            $barangKeluar->delete();

            $this->adjustStock($barangKodeDihapus, $hargaDihapus, $qtyDihapus);
        });

        return redirect()->route('barang-keluar.index')
            ->with('success', 'Data barang keluar berhasil dihapus dan stok telah dikembalikan.');
    }

    private function rules(): array
    {
        return [
            'barang_kode' => 'required|string|exists:barang,kode',
            'harga' => 'required|numeric|min:0',
            'qty' => 'required|numeric|min:1',
            'tempat_id' => 'required|integer|exists:tempat,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string',
        ];
    }

    private function applyDateFilter(Builder $query, Request $request, string $column): void
    {
        if (! $request->filled('start_date') || ! $request->filled('end_date')) {
            return;
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
        $query->whereBetween($column, [$startDate, $endDate]);
    }

    private function formatCurrency(int $value): string
    {
        return self::CURRENCY_PREFIX.number_format($value, 0, ',', '.');
    }

    private function buildActionButtons(int $recordId): string
    {
        $editUrl = route('barang-keluar.edit', $recordId);
        $btnEdit = '<a href="'.$editUrl.'" class="btn btn-sm btn-warning mr-1"><i class="fas fa-edit"></i> Edit</a>';

        $deleteFormId = 'delete-form-bk-'.$recordId;
        $deleteUrl = route('barang-keluar.destroy', $recordId);
        $btnDelete = '<form id="'.$deleteFormId.'" action="'.$deleteUrl.'" method="POST" style="display:inline;">'
            .csrf_field()
            .method_field('DELETE')
            .'<button type="submit" class="btn btn-sm btn-danger delete-btn-bk" data-form-id="'.$deleteFormId.'"><i class="fas fa-trash"></i> Hapus</button>'
            .'</form>';

        return '<div class="btn-group">'.$btnEdit.$btnDelete.'</div>';
    }

    private function findStock(string $barangKode, int $harga): ?Stok
    {
        return Stok::where('barang_kode', $barangKode)
            ->where('harga', $harga)
            ->first();
    }

    private function adjustStock(string $barangKode, int $harga, int $deltaQty): void
    {
        $stok = Stok::firstOrNew([
            'barang_kode' => $barangKode,
            'harga' => $harga,
        ]);

        $stok->jumlah = max(0, (int) ($stok->jumlah ?? 0) + $deltaQty);
        $stok->save();
    }
}
