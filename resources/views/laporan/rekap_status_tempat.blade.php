@extends('adminlte::page')

@section('title', 'Rekap Laporan per Tempat')

@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1 class="m-0 text-dark">Rekap Laporan Status per Tempat</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-default" id="date-range-btn-rekap">
                                <i class="far fa-calendar-alt"></i>&nbsp;
                                <span></span>&nbsp;
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                        <div class="col-md text-md-right">
                            <form id="form-refresh-rekap" action="{{ route('laporan.rekap-status-tempat.refresh') }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-sync"></i> Refresh Rekap
                                </button>
                            </form>
                        </div>
                    </div>

                    <table class="table table-hover table-bordered table-stripped" id="rekap-status-table" style="width:100%;">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Tempat</th>
                            <th>Total (Rp)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Notifikasi Toast
            @if (session('success'))
                const ToastSuccess = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; } });
                ToastSuccess.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif
            @if (session('error'))
                 const ToastError = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 5000, timerProgressBar: true, didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; } });
                ToastError.fire({ icon: 'error', title: '{{ session('error') }}' });
            @endif

            // --- Inisialisasi Date Range Picker (menggunakan logika periode opname) ---
            function getOpnamePeriodForDate(refDateInput) {
                let refDate = moment(refDateInput);
                if (!refDate.isValid()) { refDate = moment(); }
                let startPeriod, endPeriod;
                if (refDate.date() >= 26) {
                    startPeriod = refDate.clone().date(26);
                    endPeriod = refDate.clone().add(1, 'month').date(25);
                } else {
                    startPeriod = refDate.clone().subtract(1, 'month').date(26);
                    endPeriod = refDate.clone().date(25);
                }
                return { start: startPeriod, end: endPeriod };
            }

            let currentPeriodRekap = getOpnamePeriodForDate(moment());
            let lastPeriodRekap = getOpnamePeriodForDate(moment().subtract(1, 'month'));
            let sixMonthsAgoRefRekap = currentPeriodRekap.start.clone().subtract(5, 'months');
            let sixMonthsAgoStartRekap = getOpnamePeriodForDate(sixMonthsAgoRefRekap).start;
            let startOfYearOpnamePeriodRekap = getOpnamePeriodForDate(moment().startOf('year'));
            let maxStartDateRekap = startOfYearOpnamePeriodRekap.start.clone();

            function cbRekap(start, end, label) {
                let textToShow = label && label !== "Kustom" ? label : start.format('D MMM YY') + ' - ' + end.format('D MMM YY');
                $('#date-range-btn-rekap span').html(textToShow);
            }

            $('#date-range-btn-rekap').daterangepicker({
                startDate: currentPeriodRekap.start, 
                endDate: currentPeriodRekap.end,
                alwaysShowCalendars: true,
                ranges: {
                   'Periode Ini': [currentPeriodRekap.start, currentPeriodRekap.end],
                   'Periode Lalu': [lastPeriodRekap.start, lastPeriodRekap.end],
                   '6 Bulan Ini': [sixMonthsAgoStartRekap, currentPeriodRekap.end],
                   'Tahun Ini': [startOfYearOpnamePeriodRekap.start, currentPeriodRekap.end],
                   'Maksimal': [maxStartDateRekap, currentPeriodRekap.end],
                },
                locale: {"format": "DD/MM/YYYY", "applyLabel": "Terapkan", "cancelLabel": "Batal", "customRangeLabel": "Kustom", "daysOfWeek": ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"], "monthNames": ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],}
            }, cbRekap);
            cbRekap(currentPeriodRekap.start, currentPeriodRekap.end, "Periode Ini");

            // --- Inisialisasi DataTables ---
            let rekapTable = $('#rekap-status-table').DataTable({
                processing: true, serverSide: true, ordering: false,
                language: { url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json" },
                ajax: {
                    url: "{{ route('laporan.rekap-status-tempat.data') }}",
                    type: "GET",
                    data: function (d) {
                        let picker = $('#date-range-btn-rekap').data('daterangepicker');
                        d.start_date = picker.startDate && picker.startDate.isValid() ? picker.startDate.format('YYYY-MM-DD') : currentPeriodRekap.start.format('YYYY-MM-DD');
                        d.end_date = picker.endDate && picker.endDate.isValid() ? picker.endDate.format('YYYY-MM-DD') : currentPeriodRekap.end.format('YYYY-MM-DD');
                    },
                    error: function(jqXHR, textStatus, errorThrown) { /* ... error handler ... */ }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'nama_tempat', name: 'tempat.nama'},
                    {data: 'total', name: 'total', className: 'text-right'},
                    {data: 'status', name: 'status', className: 'text-center'},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"fB>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{ 
                    extend: 'excelHtml5', 
                    title: 'Rekap Laporan Status per Tempat', 
                    text: '<i class="fa fa-fw fa-file-excel"></i> Excel', 
                    className: 'btn btn-success btn-sm ml-2' 
                }]
            });
            
            // --- Event Handler untuk filter dan aksi ---
            $('#date-range-btn-rekap').on('apply.daterangepicker', function(ev, picker) {
                cbRekap(picker.startDate, picker.endDate, picker.chosenLabel);
                rekapTable.ajax.reload();
            });

            // Konfirmasi untuk refresh data rekap
            $('#form-refresh-rekap').on('submit', function(e) {
                e.preventDefault(); var form = this;
                Swal.fire({
                    title: 'Anda Yakin?', text: "Data rekap akan dibuat/diperbarui untuk periode saat ini.", icon: 'question',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Refresh!', cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { form.submit(); } });
            });

            // Konfirmasi untuk toggle status
            $('#rekap-status-table').on('submit', '.form-toggle-status', function(e) {
                e.preventDefault();
                var form = this;
                var actionText = $(form).find('button[type="submit"]').text().trim(); // Ambil teks dari tombol

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: "Anda akan mengubah status menjadi '" + (actionText.includes('Set Done') ? 'Done' : 'Loading') + "'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Ubah Status!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@stop