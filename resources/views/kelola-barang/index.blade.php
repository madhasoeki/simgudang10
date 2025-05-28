@extends('adminlte::page')

{{-- Mengaktifkan plugin DataTable dan Buttons --}}
@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('title_prefix', 'Kelola Barang | ')

@section('content_header')
    <h1>Kelola Barang</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Barang</h3>
            <div class="card-tools">
                <a href="{{ route('barang.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Barang</a>
            </div>
        </div>
        <div class="card-body">
            <table id="tabel-barang" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Stok</th> {{-- Kolom baru --}}
                        <th>Satuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barangs as $barang)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $barang->kode }}</td>
                            <td>{{ $barang->nama }}</td>
                            {{-- Tampilkan total stok, jika tidak ada (null), tampilkan 0 --}}
                            <td>{{ $barang->stok_sum_jumlah ?? 0 }}</td>
                            <td>{{ $barang->satuan }}</td>
                            <td>
                                <a href="{{ route('barang.edit', $barang->kode) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('barang.destroy', $barang->kode) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- Sesuaikan colspan menjadi 6 --}}
                            <td colspan="6" class="text-center">Data barang masih kosong</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#tabel-barang').DataTable({
                ordering: false,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
                },
                columnDefs: [
                    {
                        targets: 0,  // Target kolom pertama (indeks 0 adalah kolom "No")
                        width: "5%", // Alokasikan lebar sekitar 5% dari total lebar tabel
                        className: "text-center", // Atur agar teks di kolom ini menjadi di tengah
                    },
                ]
            });

            // Notifikasi SweetAlert2 untuk sukses
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

            // Konfirmasi hapus
            $('#tabel-barang').on('submit', 'form', function(e) {
                var form = this;
                e.preventDefault(); // Mencegah form dikirim langsung

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: "Data yang telah dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
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