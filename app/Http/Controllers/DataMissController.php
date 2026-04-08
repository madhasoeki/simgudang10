<?php

namespace App\Http\Controllers;

use App\Models\Opname;
use App\Support\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DataMissController extends Controller
{
    public function index()
    {
        return view('laporan.data_miss');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = Opname::with(['barang'])
                ->where('selisih', '!=', 0)
                ->select('opname.*');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();

                ReportingPeriod::applyOverlapFilter($query, 'periode_awal', 'periode_akhir', $startDate, $endDate);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('kode_barang', function ($row) {
                    return $row->barang->kode ?? '-';
                })
                ->addColumn('nama_barang', function ($row) {
                    return $row->barang->nama ?? '-';
                })
                ->addColumn('satuan_barang', function ($row) {
                    return $row->barang->satuan ?? '-';
                })
                ->addColumn('miss', function ($row) {
                    $selisih = $row->selisih;
                    $badge_class = 'secondary';
                    if ($selisih > 0) {
                        $badge_class = 'success';
                    } elseif ($selisih < 0) {
                        $badge_class = 'danger';
                    }

                    return '<span class="badge badge-'.$badge_class.'">'.$selisih.'</span>';
                })
                ->rawColumns(['miss'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }
}
