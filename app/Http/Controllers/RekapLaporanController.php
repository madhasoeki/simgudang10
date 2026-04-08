<?php

namespace App\Http\Controllers;

use App\Models\StatusTempat;
use App\Support\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class RekapLaporanController extends Controller
{
    public function index()
    {
        return view('laporan.rekap_status_tempat');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = StatusTempat::with(['tempat'])->select('status_tempat.*');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();

                ReportingPeriod::applyOverlapFilter($query, 'periode_awal', 'periode_akhir', $startDate, $endDate);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('nama_tempat', function ($row) {
                    return $row->tempat->nama ?? 'N/A';
                })
                ->editColumn('periode_awal', function ($row) {
                    return Carbon::parse($row->periode_awal)->format('d M Y');
                })
                ->editColumn('periode_akhir', function ($row) {
                    return Carbon::parse($row->periode_akhir)->format('d M Y');
                })
                ->editColumn('total', function ($row) {
                    return 'Rp '.number_format($row->total, 0, ',', '.');
                })
                ->editColumn('status', function ($row) {
                    $badgeClass = $row->status === 'done' ? 'success' : 'warning';

                    return '<span class="badge badge-'.$badgeClass.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    if (Auth::user()->can('approve opname')) {
                        $actionText = $row->status === 'loading' ? 'Set Done' : 'Set Loading';
                        $btnClass = $row->status === 'loading' ? 'btn-success' : 'btn-warning';
                        $iconClass = $row->status === 'loading' ? 'fa-check-circle' : 'fa-sync-alt';

                        $buttons .= "<form action='".route('laporan.rekap-status-tempat.toggle-status', $row->id)."' method='POST' class='d-inline form-toggle-status'>
                                        ".csrf_field()."
                                        <button type='submit' class='btn ".$btnClass." btn-xs'>
                                            <i class='fas ".$iconClass."'></i> ".$actionText.'
                                        </button>
                                    </form>';
                    }

                    return $buttons;
                })
                ->rawColumns(['status', 'action', 'total'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    public function toggleStatus(Request $request, StatusTempat $statusTempat)
    {
        try {
            $newStatus = $statusTempat->status === 'loading' ? 'done' : 'loading';
            $statusTempat->status = $newStatus;
            $statusTempat->save();

            return redirect()->route('laporan.rekap-status-tempat.index')->with('success', 'Status untuk '.$statusTempat->tempat->nama.' periode '.$statusTempat->periode_awal.' berhasil diubah menjadi '.ucfirst($newStatus).'.');
        } catch (\Throwable $exception) {
            Log::error('Error toggling status tempat.', [
                'status_tempat_id' => $statusTempat->id,
                'tempat_id' => $statusTempat->tempat_id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('laporan.rekap-status-tempat.index')->with('error', 'Gagal mengubah status.');
        }
    }
}
