<?php

namespace App\Http\Controllers;

use App\Models\BarangKeluar;
use App\Models\Tempat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class LaporanPerTempatController extends Controller
{
    private const CURRENCY_PREFIX = 'Rp ';

    public function index()
    {
        $tempats = Tempat::orderBy('nama')->get();

        return view('laporan.per_tempat', compact('tempats'));
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            if (! $request->filled('tempat_id') || empty($request->tempat_id)) {
                return $this->emptyDataTableResponse();
            }

            $query = BarangKeluar::with(['barang', 'tempat'])
                ->select('barang_keluar.*')
                ->where('tempat_id', $request->tempat_id);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                try {
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                } catch (\Throwable $exception) {
                    Log::error('Error parsing date for laporan per tempat.', [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'error' => $exception->getMessage(),
                    ]);

                    return $this->emptyDataTableResponse();
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('kode_barang', function ($row) {
                    return $row->barang->kode ?? '-';
                })
                ->editColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->format('d M Y');
                })
                ->addColumn('nama_barang', function ($row) {
                    return $row->barang->nama ?? '-';
                })
                ->addColumn('satuan_barang', function ($row) {
                    return $row->barang->satuan ?? '-';
                })
                ->editColumn('harga', function ($row) {
                    return $this->formatCurrency((int) $row->harga);
                })
                ->addColumn('jumlah_harga', function ($row) {
                    return $this->formatCurrency((int) ($row->qty * $row->harga));
                })
                ->addColumn('nama_tempat', function ($row) {
                    return $row->tempat->nama ?? '-';
                })
                ->rawColumns(['harga', 'jumlah_harga'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    private function formatCurrency(int $value): string
    {
        return self::CURRENCY_PREFIX.number_format($value, 0, ',', '.');
    }

    private function emptyDataTableResponse()
    {
        return DataTables::of(collect([]))
            ->addIndexColumn()
            ->addColumn('kode_barang', fn () => '')
            ->editColumn('tanggal', fn () => '')
            ->addColumn('nama_barang', fn () => '')
            ->addColumn('satuan_barang', fn () => '')
            ->editColumn('harga', fn () => '')
            ->addColumn('jumlah_harga', fn () => '')
            ->addColumn('nama_tempat', fn () => '')
            ->addColumn('qty', fn () => '')
            ->addColumn('keterangan', fn () => '')
            ->rawColumns(['harga', 'jumlah_harga'])
            ->make(true);
    }
}
