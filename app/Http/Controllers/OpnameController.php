<?php

namespace App\Http\Controllers;

use App\Models\Opname;
use App\Models\Stok;
use App\Support\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

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
                ReportingPeriod::applyOverlapFilter($query, 'periode_awal', 'periode_akhir', $startDate, $endDate);
            }

            return DataTables::of($query)
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
                                <span class='editable-text'>".($currentValue ?: '-')."</span>
                                <input type='text' class='form-control form-control-sm editable-input' value='{$currentValue}' style='display:none;'>
                            </div>";
                })
                ->editColumn('selisih', function ($row) {
                    if (is_null($row->total_lapangan)) {
                        return '<span class="badge badge-secondary">-</span>';
                    }

                    $currentSelisih = (int) $row->total_lapangan - (int) $row->stock_total;
                    $badgeClass = 'primary';
                    if ($currentSelisih > 0) {
                        $badgeClass = 'success';
                    } elseif ($currentSelisih < 0) {
                        $badgeClass = 'danger';
                    }

                    return '<span class="badge badge-'.$badgeClass.'">'.$currentSelisih.'</span>';
                })
                ->addColumn('actions', function ($row) {
                    if (! $row->approved) {
                        return $this->buildEditableActionsColumn($row);
                    }

                    return '<span class="badge badge-success">Approved at: '.($row->approved_at ? Carbon::parse($row->approved_at)->format('d M Y H:i') : 'N/A').'</span>';
                })
                ->rawColumns(['total_lapangan', 'keterangan', 'selisih', 'actions'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid request.'], 400);
    }

    public function update(Request $request, Opname $opname)
    {
        $validatedData = $request->validate($this->rules());

        if ($opname->approved) {
            return redirect()->route('opname.index')->with('error', 'Data yang sudah diapprove tidak bisa diubah.');
        }

        $keteranganValidation = $this->validateKeteranganForDifference(
            $request,
            $opname,
            (int) $validatedData['total_lapangan'],
            $validatedData['keterangan'] ?? null,
            ''
        );
        if ($keteranganValidation) {
            return $keteranganValidation;
        }

        $opname->total_lapangan = (int) $validatedData['total_lapangan'];
        $opname->keterangan = $validatedData['keterangan'] ?? null;
        $opname->selisih = $this->calculateDifference((int) $validatedData['total_lapangan'], (int) $opname->stock_total);
        $opname->save();

        if ($request->ajax()) {
            return response()->json(['message' => 'Data opname untuk '.$opname->barang->nama.' berhasil disimpan.']);
        }

        return redirect()->route('opname.index')->with('success', 'Data opname untuk '.$opname->barang->nama.' berhasil disimpan.');
    }

    public function approve(Request $request, Opname $opname)
    {
        $validatedData = $request->validate($this->rules());

        if ($opname->approved) {
            return redirect()->route('opname.index')->with('error', 'Data ini sudah pernah diapprove.');
        }

        $keteranganValidation = $this->validateKeteranganForDifference(
            $request,
            $opname,
            (int) $validatedData['total_lapangan'],
            $validatedData['keterangan'] ?? null,
            ' sebelum approve'
        );
        if ($keteranganValidation) {
            return $keteranganValidation;
        }

        $opname->total_lapangan = (int) $validatedData['total_lapangan'];
        $opname->keterangan = $validatedData['keterangan'] ?? null;
        $opname->selisih = $this->calculateDifference((int) $validatedData['total_lapangan'], (int) $opname->stock_total);
        $opname->save();

        if (is_null($opname->total_lapangan)) {
            return redirect()->back()->with('error', 'Data lapangan harus diisi sebelum melakukan approval untuk '.$opname->barang->nama);
        }

        DB::beginTransaction();
        try {
            $this->adjustStock($opname);
            $opname->update([
                'approved' => true,
                'approved_at' => now(),
            ]);
            DB::commit();

            return redirect()->route('opname.index')->with('success', 'Opname untuk '.$opname->barang->nama.' berhasil diapprove.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error approving opname.', [
                'opname_id' => $opname->id,
                'barang_kode' => $opname->barang_kode,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('opname.index')->with('error', 'Approval gagal: '.$e->getMessage());
        }
    }

    public function cancelApproval(Opname $opname): RedirectResponse
    {
        if (! $opname->approved) {
            return redirect()->route('opname.index')->with('error', 'Data ini belum diapprove.');
        }

        DB::beginTransaction();
        try {
            $this->reverseStockAdjustment($opname);
            $opname->update([
                'approved' => false,
                'approved_at' => null,
            ]);
            DB::commit();

            return redirect()->route('opname.index')->with('success', 'Approval opname berhasil dibatalkan untuk '.($opname->barang->nama ?? $opname->barang_kode).'.');
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Error canceling opname approval.', [
                'opname_id' => $opname->id,
                'barang_kode' => $opname->barang_kode,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('opname.index')->with('error', 'Pembatalan approval gagal: '.$exception->getMessage());
        }
    }

    private function adjustStock(Opname $opname)
    {
        $selisih = (int) $opname->selisih;
        if ($selisih === 0) {
            return;
        }

        if ($selisih > 0) {
            $this->increaseStock($opname, $selisih);
        } else {
            $this->decreaseStock($opname, abs($selisih));
        }
    }

    private function reverseStockAdjustment(Opname $opname): void
    {
        $selisih = (int) $opname->selisih;
        if ($selisih === 0) {
            return;
        }

        if ($selisih > 0) {
            $this->decreaseStock($opname, $selisih);
        } else {
            $this->increaseStock($opname, abs($selisih));
        }
    }

    private function decreaseStock(Opname $opname, int $jumlahPengurangan): void
    {
        $stoksToUpdate = Stok::where('barang_kode', $opname->barang_kode)
            ->where('jumlah', '>', 0)
            ->orderBy('harga', 'asc')
            ->get();

        $sisaPengurangan = $jumlahPengurangan;
        foreach ($stoksToUpdate as $stok) {
            if ($sisaPengurangan <= 0) {
                break;
            }

            $jumlahDapatDikurangi = min($stok->jumlah, $sisaPengurangan);
            $stok->jumlah -= $jumlahDapatDikurangi;
            $stok->save();
            $sisaPengurangan -= $jumlahDapatDikurangi;
        }
    }

    private function increaseStock(Opname $opname, int $jumlahPenambahan): void
    {
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

    private function rules(): array
    {
        return [
            'total_lapangan' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    private function calculateDifference(int $totalLapangan, int $stockTotal): int
    {
        return $totalLapangan - $stockTotal;
    }

    private function validateKeteranganForDifference(
        Request $request,
        Opname $opname,
        int $totalLapangan,
        ?string $keterangan,
        string $messageSuffix
    ): ?RedirectResponse {
        $selisih = $this->calculateDifference($totalLapangan, (int) $opname->stock_total);
        if ($selisih !== 0 && empty($keterangan) && ! ($totalLapangan === 0 && (int) $opname->stock_total === 0)) {
            return redirect()->back()
                ->withInput($request->except('keterangan'))
                ->with('error', 'Keterangan wajib diisi jika Stok Lapangan berbeda dengan Stok Sistem untuk barang '.$opname->barang->nama.$messageSuffix.'.');
        }

        return null;
    }

    private function buildEditableActionsColumn(Opname $row): string
    {
        $opnameId = $row->id;
        $buttons = "<button type='button' class='btn btn-xs btn-info btn-edit-row mr-1' data-opname-id='{$opnameId}' title='Edit Data Lapangan & Keterangan'><i class='fa fa-pencil-alt'></i></button>";
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

        return $buttons;
    }
}
