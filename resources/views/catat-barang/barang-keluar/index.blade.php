@extends('adminlte::page')

{{-- Mengaktifkan plugin yang dibutuhkan --}}
@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@section('title', 'Kelola Barang Keluar')

@section('content_header')
    <h1>Kelola Barang Keluar</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Riwayat Barang Keluar</h3>
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
                {{-- Tombol Tambah Data di Kanan --}}
                <a href="{{ route('barang-keluar.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Catat Barang Keluar
                </a>
            </div>
        </div>

        {{-- Tabel Data --}}
        <table class="table table-bordered table-striped" id="table-barang-keluar" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Tujuan</th>
                    <th>Keterangan</th>
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
    // Tampilkan notifikasi jika ada dari session
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

    // 2. Inisialisasi DataTables
    var dataTable = $('#table-barang-keluar').DataTable({
        processing: true,
        serverSide: true,
        ordering: false, 
        language: {
            url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
        },
        ajax: {
            url: '{{ route('barang-keluar.data') }}',
            data: function (d) {
                d.start_date = $('#date-range-btn').data('daterangepicker').startDate.format('YYYY-MM-DD');
                d.end_date = $('#date-range-btn').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '10px' }, // No tengahkan
            { data: 'tanggal', name: 'barang_keluar.tanggal' },
            { data: 'kode_barang', name: 'barang.kode', width: '90px' }, // Kode Barang dengan lebar tetap
            { data: 'nama_barang', name: 'barang.nama' },
            { data: 'qty', name: 'qty', className: 'text-center' },
            { data: 'harga', name: 'harga', className: 'text-right' },
            { data: 'jumlah', name: 'jumlah', className: 'text-right' },
            { data: 'nama_tempat', name: 'tempat.nama' },
            { data: 'keterangan', name: 'barang_keluar.keterangan' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', width: '150px' } // Aksi dengan lebar tetap
        ],
        dom:  '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-md-end"fB>>' + 
              '<"row"<"col-sm-12"tr>>' +
              '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [ 
            {
                extend: 'excelHtml5',
                title: 'Data Barang Keluar',
                text: '<i class="fa fa-fw fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm ml-2' 
            }
        ]
    });
    
    $('#date-range-btn').on('apply.daterangepicker', function(ev, picker) {
        cb(picker.startDate, picker.endDate, picker.chosenLabel);
        dataTable.ajax.reload();
    });

    // Konfirmasi sebelum menghapus data
    $('#table-barang-keluar').on('click', '.delete-btn-bk', function(e) {
        e.preventDefault(); 
        var formId = $(this).data('form-id'); 
        var form = $('#' + formId);

        Swal.fire({
            title: 'Anda Yakin?',
            text: "Data barang keluar ini akan dihapus secara permanen dan stok akan dikembalikan!", // Sesuaikan pesan
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus Saja!',
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