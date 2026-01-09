@extends('adminlte::page')

{{-- Mengaktifkan plugin yang dibutuhkan --}}
@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@section('title', 'Kelola Barang Masuk')

@section('content_header')
    <h1>Kelola Barang Masuk</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Riwayat Barang Masuk</h3>
    </div>
    <div class="card-body">
        {{-- Baris untuk Tombol Aksi --}}
        <div class="row mb-3">
            <div class="col-md-6">
                {{-- Tombol DateRangePicker di Kiri --}}
                <button type="button" class="btn btn-default" id="date-range-btn">
                    <i class="far fa-calendar-alt"></i>&nbsp;
                    <span></span>&nbsp;
                    <i class="fa fa-caret-down"></i>
                </button>
            </div>
            <div class="col-md-6 text-md-right">
                {{-- Menggunakan btn-toolbar untuk mengelompokkan grup tombol --}}
                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups" style="justify-content: flex-end;">
                    <div class="btn-group mr-2" role="group" aria-label="Tambah Data Group">
                        <a href="{{ route('barang-masuk.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Data
                        </a>
                    </div>
                    {{-- Wadah khusus untuk tombol DataTables --}}
                    <div id="export-buttons-placeholder" class="btn-group" role="group" aria-label="Export Group">
                        {{-- Tombol Export Excel akan ditempatkan di sini oleh JavaScript --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Data --}}
        <table class="table table-bordered table-striped" id="table-barang-masuk" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    @if (session('success'))
        // Konfigurasi untuk notifikasi toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end', // Posisi di pojok kanan atas
            showConfirmButton: false,
            timer: 3000, // Notifikasi akan hilang setelah 3 detik
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        // Tampilkan notifikasi toast-nya
        Toast.fire({
            icon: 'success',
            title: '{{ session('success') }}' // Ambil pesan dari session
        });
    @endif

    // 1. Inisialisasi Date Range Picker
    // (Kode DateRangePicker Anda dari sebelumnya sudah benar)
    var start = moment().subtract(6, 'days'); 
    var end = moment();
    var initialLabel = "7 Hari Terakhir"; 

    function cb(start, end, label) { 
        var textToShow;
        if (label && label !== "Kustom") { 
            textToShow = label;
        } else {
            textToShow = start.format('D MMMM, YYYY') + ' - ' + end.format('D MMMM, YYYY');
        }
        $('#date-range-btn span').html(textToShow);
    }

    $('#date-range-btn').daterangepicker({
        startDate: start,
        endDate: end,
        alwaysShowCalendars: true,
        ranges: {
           'Hari ini': [moment(), moment()],
           'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
           '14 Hari Terakhir': [moment().subtract(13, 'days'), moment()],
           'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
           'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           'Maksimal': [moment().startOf('year'), moment()]
        },
        locale: {
            "format": "DD/MM/YYYY", 
            "applyLabel": "Terapkan",
            "cancelLabel": "Batal",
            "fromLabel": "Dari",
            "toLabel": "Ke",
            "customRangeLabel": "Kustom", 
            "daysOfWeek": ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
            "monthNames": ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
        }
    }, cb);

    cb(start, end, initialLabel);

    // 2. Inisialisasi DataTables dengan DOM yang diperbarui
    // ----------------------------------------------------
    var dataTable = $('#table-barang-masuk').DataTable({
        processing: true,
        serverSide: true,
        ordering: false, 
        language: {
            url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
        },
        ajax: {
            url: '{{ route('barang-masuk.data') }}',
            data: function (d) {
                d.start_date = $('#date-range-btn').data('daterangepicker').startDate.format('YYYY-MM-DD');
                d.end_date = $('#date-range-btn').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '10px' }, // No tengahkan
            { data: 'tanggal', name: 'barang_masuk.tanggal' }, // Tanggal dengan lebar tetap
            { data: 'kode_barang', name: 'barang.kode' }, // Kode Barang dengan lebar tetap
            { data: 'nama', name: 'barang.nama' }, // Nama Barang dengan lebar tetap
            { data: 'qty', name: 'qty', className: 'text-center' }, // Qty tengahkan
            { data: 'harga', name: 'harga', className: 'text-right' }, // Harga dengan lebar tetap
            { data: 'jumlah', name: 'jumlah', className: 'text-right' }, // Jumlah dengan lebar tetap
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', width: '150px' } // Aksi dengan lebar tetap
        ],
        // Mengatur tata letak kontrol DataTables: l (length), f (filter/search), B (buttons), t (table), i (info), p (pagination)
        // 'fB' akan membuat tombol muncul di sebelah kanan search field jika ada cukup ruang.
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"fB>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [ // Definisi tombol
            {
                extend: 'excelHtml5',
                title: 'Data Barang Masuk',
                text: '<i class="fa fa-fw fa-file-excel"></i> Excel',
                className: 'btn btn-success ml-2' // Tombol kecil dengan margin kiri
            }
        ]
    });
    
    // Baris 'dataTable.buttons().container().appendTo(...);' sudah tidak diperlukan lagi
    // karena 'B' sudah ada di dalam string 'dom'.

    // 3. Hubungkan Event Picker ke DataTables
    $('#date-range-btn').on('apply.daterangepicker', function(ev, picker) {
        cb(picker.startDate, picker.endDate, picker.chosenLabel);
        dataTable.ajax.reload();
    });
    
    // Konfirmasi untuk menghapus data
    $('#table-barang-masuk').on('click', '.delete-btn', function(e) {
        e.preventDefault(); // Mencegah form dikirim langsung
        var formId = $(this).data('form-id'); // Ambil ID form dari atribut data-form-id
        var form = $('#' + formId);

        Swal.fire({
            title: 'Anda Yakin?',
            text: "Data barang masuk ini akan dihapus secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus Saja!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Jika dikonfirmasi, kirim form
            }
        });
    });
});
</script>
@stop