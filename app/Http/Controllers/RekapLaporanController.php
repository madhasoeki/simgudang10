<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatusTempat;
use App\Models\Tempat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;


class RekapLaporanController extends Controller
{
    public function index()
    {
        // Data akan otomatis terupdate melalui event-driven system
        // Setiap transaksi barang keluar akan otomatis trigger kalkulasi status tempat
        return view('laporan.rekap_status_tempat');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $query = StatusTempat::with(['tempat'])->select('status_tempat.*');

            // Filter periode standar untuk DateRangePicker
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->where('periode_awal', '<=', $endDate)
                      ->where('periode_akhir', '>=', $startDate);
                });
            }

            return Datatables::of($query)
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
                    return 'Rp ' . number_format($row->total, 0, ',', '.');
                })
                ->editColumn('status', function ($row) {
                    $badgeClass = $row->status === 'done' ? 'success' : 'warning';
                    return '<span class="badge badge-'.$badgeClass.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    // Tombol untuk mengubah status, hanya jika user memiliki permission tertentu (misal 'manage rekap')
                    // Untuk sekarang, kita buat bisa diakses oleh admin/super-admin
                    // Pastikan ada permission yang sesuai jika ingin dibatasi lebih lanjut
                    if (Auth::user()->can('approve opname')) { // Menggunakan permission opname untuk contoh
                        $actionText = $row->status === 'loading' ? 'Set Done' : 'Set Loading';
                        $btnClass = $row->status === 'loading' ? 'btn-success' : 'btn-warning';
                        $iconClass = $row->status === 'loading' ? 'fa-check-circle' : 'fa-sync-alt';
                        
                        $buttons .= "<form action='".route('laporan.rekap-status-tempat.toggle-status', $row->id)."' method='POST' class='d-inline form-toggle-status'>
                                        ".csrf_field()."
                                        <button type='submit' class='btn ".$btnClass." btn-xs'>
                                            <i class='fas ".$iconClass."'></i> ".$actionText."
                                        </button>
                                    </form>";
                    }
                    return $buttons;
                })
                ->rawColumns(['status', 'action', 'total'])
                ->make(true);
        }
    }

    public function toggleStatus(Request $request, StatusTempat $statusTempat)
    {
        // Pastikan user memiliki otorisasi (misalnya, menggunakan Gate atau Policy)
        // if (!Auth::user()->can('manage rekap')) {
        //     return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengubah status.');
        // }

        try {
            $newStatus = $statusTempat->status === 'loading' ? 'done' : 'loading';
            $statusTempat->status = $newStatus;
            $statusTempat->save(); // Trait LogsActivity akan mencatat ini jika ada di model StatusTempat
            return redirect()->route('laporan.rekap-status-tempat.index')->with('success', 'Status untuk '.$statusTempat->tempat->nama.' periode '.$statusTempat->periode_awal.' berhasil diubah menjadi '.ucfirst($newStatus).'.');
        } catch (\Exception $e) {
            Log::error('Error toggling status tempat: ' . $e->getMessage());
            return redirect()->route('laporan.rekap-status-tempat.index')->with('error', 'Gagal mengubah status.');
        }
    }
}