@extends('adminlte::page')

@section('title', 'Laporan Data Miss')

{{-- Aktifkan plugin AdminLTE --}}
@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@section('content_header')
    <h1 class="m-0 text-dark">Laporan Data Miss/Selisih</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-default" id="date-range-btn-miss"> {{-- ID unik --}}
                                <i class="far fa-calendar-alt"></i>&nbsp;
                                <span></span>&nbsp;
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                        {{-- Tidak ada tombol refresh di sini, karena ini hanya laporan --}}
                    </div>

                    <table class="table table-hover table-bordered table-stripped" id="data-miss-table" style="width:100%;">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Miss</th>
                            <th>Keterangan</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {

            // --- Inisialisasi Date Range Picker ---
            // Menggunakan fungsi dan variabel periode yang sama seperti di opname.blade.php
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

            let currentPeriod = getOpnamePeriodForDate(moment());
            let lastPeriod = getOpnamePeriodForDate(moment().subtract(1, 'month'));
            let sixMonthsAgoRef = currentPeriod.start.clone().subtract(5, 'months');
            let sixMonthsAgoStart = getOpnamePeriodForDate(sixMonthsAgoRef).start;
            let startOfYearOpnamePeriod = getOpnamePeriodForDate(moment().startOf('year'));
            let maxStartDate = startOfYearOpnamePeriod.start.clone();

            function cbMiss(start, end, label) { // Fungsi callback unik
                let textToShow = label && label !== "Kustom" ? label : start.format('D MMM YY') + ' - ' + end.format('D MMM YY');
                $('#date-range-btn-miss span').html(textToShow);
            }

            $('#date-range-btn-miss').daterangepicker({ // ID unik
                startDate: currentPeriod.start, endDate: currentPeriod.end, alwaysShowCalendars: true,
                ranges: {
                   'Periode Ini': [currentPeriod.start, currentPeriod.end],
                   'Periode Lalu': [lastPeriod.start, lastPeriod.end],
                   '6 Bulan Ini': [sixMonthsAgoStart, currentPeriod.end],
                   'Tahun Ini': [startOfYearOpnamePeriod.start, currentPeriod.end],
                   'Maksimal': [maxStartDate, currentPeriod.end],
                },
                locale: {"format": "DD/MM/YYYY", "applyLabel": "Terapkan", "cancelLabel": "Batal", "customRangeLabel": "Kustom", "daysOfWeek": ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"], "monthNames": ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],}
            }, cbMiss);
            cbMiss(currentPeriod.start, currentPeriod.end, "Periode Ini");


            // --- Inisialisasi DataTables ---
            let dataMissTable = $('#data-miss-table').DataTable({ // ID tabel unik
                processing: true, serverSide: true, ordering: false, // Boleh ada ordering jika mau
                language: { url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json" },
                ajax: {
                    url: "{{ route('data-miss.data') }}", // Route baru untuk data miss
                    type: "GET",
                    data: function (d) {
                        let picker = $('#date-range-btn-miss').data('daterangepicker');
                        d.start_date = picker.startDate && picker.startDate.isValid() ? picker.startDate.format('YYYY-MM-DD') : currentPeriod.start.format('YYYY-MM-DD');
                        d.end_date = picker.endDate && picker.endDate.isValid() ? picker.endDate.format('YYYY-MM-DD') : currentPeriod.end.format('YYYY-MM-DD');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("DataTables AJAX Error (Data Miss):", { status: jqXHR.status, statusText: jqXHR.statusText, responseText: jqXHR.responseText, textStatus: textStatus, errorThrown: errorThrown });
                        Swal.fire({ icon: 'error', title: 'Gagal Memuat Data Miss', text: 'Terjadi masalah saat mengambil data. Coba muat ulang halaman.', footer: '<small>Detail error ada di console browser (F12).</small>' });
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'kode_barang', name: 'barang.kode'}, // Dari addColumn di controller
                    {data: 'nama_barang', name: 'barang.nama'}, // Dari addColumn di controller
                    {data: 'satuan_barang', name: 'barang.satuan', searchable: false, orderable: false}, // Dari addColumn
                    {data: 'miss', name: 'selisih', className: 'text-center'}, // Ini adalah kolom 'selisih' dari opname
                    {data: 'keterangan', name: 'keterangan'},
                ],
                // DOM dan Buttons bisa disamakan dengan Opname atau disesuaikan
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"fB>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{ extend: 'excelHtml5', title: 'Laporan Data Miss', text: '<i class="fa fa-fw fa-file-excel"></i> Excel', className: 'btn btn-success ml-2' }]
            });
            
            // --- Event Handler ---
            $('#date-range-btn-miss').on('apply.daterangepicker', function(ev, picker) {
                cbMiss(picker.startDate, picker.endDate, picker.chosenLabel);
                dataMissTable.ajax.reload();
            });
        });
    </script>
@stop