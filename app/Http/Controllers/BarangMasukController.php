<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Stok;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BarangMasukController extends Controller
{
    private const CURRENCY_PREFIX = 'Rp ';

    public function index()
    {
        return view('catat-barang.barang-masuk.index');
    }

    public function data(Request $request)
    {
        $query = DB::table('barang_masuk')
            ->join('barang', 'barang_masuk.barang_kode', '=', 'barang.kode')
            ->join('users', 'barang_masuk.user_id', '=', 'users.id')
            ->select([
                'barang_masuk.id',
                'barang_masuk.tanggal',
                'barang_masuk.qty',
                'barang_masuk.harga',
                'barang_masuk.jumlah',
                'barang.nama',
                'barang.kode as kode_barang',
                'users.name as user_name',
            ]);

        $this->applyDateFilter($query, $request, 'barang_masuk.tanggal');

        $query->orderBy('barang_masuk.tanggal', 'desc')
            ->orderBy('barang_masuk.id', 'desc');

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
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d');

        return view('catat-barang.barang-masuk.create', compact('barangs', 'today'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->rules());

        DB::transaction(function () use ($validatedData) {
            $qty = (int) $validatedData['qty'];
            $harga = (int) $validatedData['harga'];

            BarangMasuk::create([
                'tanggal' => $validatedData['tanggal'],
                'barang_kode' => $validatedData['barang_kode'],
                'qty' => $qty,
                'harga' => $harga,
                'jumlah' => $qty * $harga,
                'user_id' => Auth::id(),
            ]);

            $this->adjustStock($validatedData['barang_kode'], $harga, $qty);
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil ditambahkan.');
    }

    public function edit(BarangMasuk $barangMasuk)
    {
        $barangs = Barang::orderBy('nama', 'asc')->get();
        $today = Carbon::now(config('app.timezone'))->format('Y-m-d');

        return view('catat-barang.barang-masuk.edit', compact('barangMasuk', 'barangs', 'today'));
    }

    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        $validatedData = $request->validate($this->rules());

        DB::transaction(function () use ($validatedData, $barangMasuk) {
            $oldBarangKode = $barangMasuk->barang_kode;
            $oldHarga = (int) $barangMasuk->harga;
            $oldQty = (int) $barangMasuk->qty;

            $newQty = (int) $validatedData['qty'];
            $newHarga = (int) $validatedData['harga'];

            $this->adjustStock($oldBarangKode, $oldHarga, -$oldQty);

            $barangMasuk->update([
                'tanggal' => $validatedData['tanggal'],
                'barang_kode' => $validatedData['barang_kode'],
                'qty' => $newQty,
                'harga' => $newHarga,
                'jumlah' => $newQty * $newHarga,
            ]);

            $this->adjustStock($validatedData['barang_kode'], $newHarga, $newQty);
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil diperbarui.');
    }

    public function destroy(BarangMasuk $barangMasuk)
    {
        DB::transaction(function () use ($barangMasuk) {
            $barangKodeToDelete = $barangMasuk->barang_kode;
            $hargaToDelete = (int) $barangMasuk->harga;
            $qtyToDelete = (int) $barangMasuk->qty;

            $barangMasuk->delete();

            $this->adjustStock($barangKodeToDelete, $hargaToDelete, -$qtyToDelete);
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'barang_kode' => 'required|string|exists:barang,kode',
            'qty' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'tanggal' => 'required|date|before_or_equal:today',
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
        $editUrl = route('barang-masuk.edit', $recordId);
        $btnEdit = '<a href="'.$editUrl.'" class="btn btn-sm btn-warning mr-1"><i class="fas fa-edit"></i> Edit</a>';

        $deleteFormId = 'delete-form-'.$recordId;
        $deleteUrl = route('barang-masuk.destroy', $recordId);
        $btnDelete = '<form id="'.$deleteFormId.'" action="'.$deleteUrl.'" method="POST" style="display:inline;">'
            .csrf_field()
            .method_field('DELETE')
            .'<button type="submit" class="btn btn-sm btn-danger delete-btn" data-form-id="'.$deleteFormId.'"><i class="fas fa-trash"></i> Hapus</button>'
            .'</form>';

        return '<div class="btn-group">'.$btnEdit.$btnDelete.'</div>';
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
