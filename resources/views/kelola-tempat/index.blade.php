@extends('adminlte::page')

@section('title_prefix', 'Kelola Tempat | ')

@section('content_header')
    <h1>Kelola Tempat</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Tempat</h3>
            <div class="card-tools">
                <a href="{{ route('tempat.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Tempat</a>
            </div>
        </div>
        <div class="card-body">
            <table id="tabel-tempat" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Tempat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tempats as $tempat)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $tempat->nama }}</td>
                            <td>
                                <a href="{{ route('tempat.edit', $tempat->id) }}" class="btn btn-warning btn-xs">Edit</a>
                                <form action="{{ route('tempat.destroy', $tempat->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Data tempat masih kosong</td>
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
            $('#tabel-tempat').DataTable({
                "ordering": false,
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
                },
                "columnDefs": [
                    {
                        "targets": 0,  // Target kolom pertama (indeks 0 adalah kolom "No")
                        "width": "1%", // Alokasikan lebar sekitar 5% dari total lebar tabel
                        "className": "text-center", // Atur agar teks di kolom ini menjadi di tengah
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
            $('#tabel-tempat').on('submit', 'form', function(e) {
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
                        form.submit();
                    }
                });
            });
        });
    </script>
@stop