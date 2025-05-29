@extends('adminlte::page')

@section('title', 'Laporan Barang Keluar per Tempat')

@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Select2', true)


@section('content_header')
    <h1 class="m-0 text-dark">Laporan Barang Keluar per Tempat</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between mb-3">
                        {{-- Filter Tanggal --}}
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-default btn-block" id="date-range-btn-per-tempat">
                                <i class="far fa-calendar-alt"></i>&nbsp;
                                <span></span>&nbsp;
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                        {{-- Filter Tempat --}}
                        <div class="col-md-2">
                            <select class="form-control" id="filter-tempat">
                                <option value="">Semua Tempat</option>
                                @foreach($tempats as $tempat)
                                    <option value="{{ $tempat->id }}">{{ $tempat->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table class="table table-hover table-bordered table-stripped" id="laporan-per-tempat-table" style="width:100%;">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>QTY</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Keterangan</th>
                            <th>Jumlah</th>
                            <th>Tempat</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .form-group .select2-container {
        width: 100% !important;
    }
    .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
        padding: .375rem .75rem !important;
        line-height: 1.5 !important;
    }
    .select2-selection__arrow {
        height: calc(2.25rem + 2px) !important;
    }
</style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#filter-tempat').select2({
                placeholder: "Pilih Tempat Dahulu", // Ubah placeholder
                allowClear: true
            });

            // Fungsi untuk menghitung periode opname (jika masih ingin menggunakan logika periode 26-25)
            // Jika untuk laporan barang keluar ingin tanggal kalender biasa, fungsi ini bisa disederhanakan/dihapus
            function getOpnameStylePeriodForDate(refDateInput) {
                let refDate = moment(refDateInput);
                if (!refDate.isValid()) { 
                    refDate = moment(); 
                }
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

            // --- Inisialisasi Date Range Picker ---
            // Untuk laporan barang keluar, mungkin lebih intuitif menggunakan periode kalender standar
            // Namun, jika ingin konsisten dengan Opname, kita bisa tetap pakai logika periode 26-25
            
            // Default: Periode Ini (mengikuti logika opname)
            let initialDateRange = getOpnameStylePeriodForDate(moment()); 

            // Definisikan semua preset yang dibutuhkan
            let currentOpnamePeriod = getOpnameStylePeriodForDate(moment());
            let lastOpnamePeriod = getOpnameStylePeriodForDate(moment().subtract(1, 'month'));
            
            let sixMonthsAgoRef = moment().subtract(5, 'months');
            let sixMonthsAgoOpnameStart = getOpnameStylePeriodForDate(sixMonthsAgoRef).start;

            let startOfYearRef = moment().startOf('year');
            let startOfYearOpnameRange = getOpnameStylePeriodForDate(startOfYearRef);
            let maxOpnameStartDate = startOfYearOpnameRange.start.clone();


            function cbPerTempat(start, end, label) {
                let textToShow = label && label !== "Kustom" ? label : start.format('D MMM YY') + ' - ' + end.format('D MMM YY');
                $('#date-range-btn-per-tempat span').html(textToShow);
            }

            $('#date-range-btn-per-tempat').daterangepicker({
                startDate: initialDateRange.start, 
                endDate: initialDateRange.end,
                alwaysShowCalendars: true,
                autoUpdateInput: false, // Jangan update input otomatis, kita handle via cbPerTempat
                ranges: {
                   'Periode Ini ': [currentOpnamePeriod.start, currentOpnamePeriod.end],
                   'Periode Lalu ': [lastOpnamePeriod.start, lastOpnamePeriod.end],
                   '6 Bulan Ini ': [sixMonthsAgoOpnameStart, currentOpnamePeriod.end],
                   'Tahun Ini ': [startOfYearOpnameRange.start, currentOpnamePeriod.end],
                   'Maksimal ': [maxOpnameStartDate, currentOpnamePeriod.end],
                },
                locale: {
                    "format": "DD/MM/YYYY", "applyLabel": "Terapkan", "cancelLabel": "Batal",
                    "customRangeLabel": "Kustom",
                    "daysOfWeek": ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                    "monthNames": ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                }
            });
            // Panggil cbPerTempat untuk set label awal, tapi JANGAN reload tabel dulu
            cbPerTempat(initialDateRange.start, initialDateRange.end, "Periode Ini ");
            // Kosongkan nilai awal agar tidak langsung apply filter
            $('#date-range-btn-per-tempat').val('');


            // --- Inisialisasi DataTables ---
            let laporanTable = $('#laporan-per-tempat-table').DataTable({
                processing: true, serverSide: true, ordering: false,
                deferLoading: 0, // ATURAN BARU: Jangan load data saat inisialisasi
                language: { url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json" },
                ajax: {
                    url: "{{ route('laporan.per-tempat.data') }}",
                    type: "GET",
                    data: function (d) {
                        let picker = $('#date-range-btn-per-tempat').data('daterangepicker');
                        
                        // Default ke periode saat ini JIKA picker atau tanggalnya tidak valid
                        let startDateToSend = currentOpnamePeriod.start.format('YYYY-MM-DD');
                        let endDateToSend = currentOpnamePeriod.end.format('YYYY-MM-DD');

                        if (picker && picker.startDate && picker.startDate.isValid()) {
                            startDateToSend = picker.startDate.format('YYYY-MM-DD');
                        }
                        if (picker && picker.endDate && picker.endDate.isValid()) {
                            endDateToSend = picker.endDate.format('YYYY-MM-DD');
                        }
                        
                        d.start_date = startDateToSend;
                        d.end_date = endDateToSend;
                        d.tempat_id = $('#filter-tempat').val();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("DataTables AJAX Error (Laporan Per Tempat):", { status: jqXHR.status, statusText: jqXHR.statusText, responseText: jqXHR.responseText, textStatus: textStatus, errorThrown: errorThrown });
                        Swal.fire({ icon: 'error', title: 'Gagal Memuat Data', text: 'Terjadi masalah saat mengambil data. Coba muat ulang halaman.', footer: '<small>Detail error ada di console browser (F12).</small>' });
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'kode_barang', name: 'barang.kode'},
                    {data: 'tanggal', name: 'tanggal'}, // Nama kolom 'tanggal' sesuai informasimu
                    {data: 'nama_barang', name: 'barang.nama'},
                    {data: 'qty', name: 'qty', className: 'text-center'},
                    {data: 'satuan_barang', name: 'barang.satuan', searchable: false, orderable: false},
                    {data: 'harga', name: 'harga', className: 'text-right'},
                    {data: 'keterangan', name: 'keterangan'},
                    {data: 'jumlah_harga', name: 'jumlah_harga', className: 'text-right', searchable: false, orderable: false},
                    {data: 'nama_tempat', name: 'tempat.nama'},
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"fB>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{ extend: 'excelHtml5', title: 'Laporan Stok Opname', text: '<i class="fa fa-fw fa-file-excel"></i> Excel', className: 'btn btn-success ml-2' }]
            });
            
            // --- Event Handler untuk filter ---
            $('#date-range-btn-per-tempat').on('apply.daterangepicker', function(ev, picker) {
                cbPerTempat(picker.startDate, picker.endDate, picker.chosenLabel);
                // Hanya reload jika tempat sudah dipilih
                if ($('#filter-tempat').val()) {
                    laporanTable.ajax.reload();
                }
            });
            // Saat daterangepicker dikosongkan (jika ada tombol clear atau memilih custom range kosong)
             $('#date-range-btn-per-tempat').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val(''); // Kosongkan inputnya
                 cbPerTempat(initialDateRange.start, initialDateRange.end, "Periode Ini "); // Reset label
                 if ($('#filter-tempat').val()) { // Reload hanya jika tempat dipilih
                    laporanTable.ajax.reload();
                }
            });


            $('#filter-tempat').on('change', function() {
                // ATURAN BARU: Hanya reload jika tempat_id ada isinya
                if ($(this).val()) {
                    laporanTable.ajax.reload();
                } else {
                    laporanTable.clear().draw(); // Kosongkan tabel jika "Semua Tempat" dipilih
                }
            });
        });
    </script>
@stop