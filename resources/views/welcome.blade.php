@extends('adminlte::page')

@section('plugins.Datatables', true)

@section('title_prefix', 'Dashboard | ')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    {{-- Baris untuk Info Box --}}
    <div class="row">
        <div class="col-sm">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Barang Masuk Hari Ini</span>
                    <span class="info-box-number">{{ $barangMasukHariIni ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-sm">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Barang Keluar Hari Ini</span>
                    <span class="info-box-number">{{ $barangKeluarHariIni ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-sm">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Stok Menipis</span>
                    {{-- Perbaikan: $stokMenipis sudah berupa angka (integer) --}}
                    <span class="info-box-number">{{ $stokMenipis ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Card untuk Tabel Stok Barang Utama --}}
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Stok Barang Saat Ini</h3>
        </div>
        <div class="card-body">
            <table id="tabel-stok" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($barangs as $barang)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $barang->kode }}</td>
                            <td>{{ $barang->nama }}</td>
                            {{-- Tampilkan total stok, jika tidak ada (null), tampilkan 0 --}}
                            <td>{{ $barang->stok_sum_jumlah ?? 0 }}</td>
                            <td>{{ $barang->satuan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Baris untuk dua tabel transaksi harian --}}
    <div class="row">
        {{-- Card Tabel Barang Masuk Hari Ini --}}
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Barang Masuk Hari Ini</h3>
                </div>
                <div class="card-body table-responsive p-0" style="height: 300px;">
                    <table class="table table-head-fixed text-nowrap">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transaksiMasukHariIni as $transaksi)
                                <tr>
                                    <td>{{ $transaksi->barang->nama }}</td>
                                    <td>{{ $transaksi->qty }} {{ $transaksi->barang->satuan }}</td>
                                    <td>{{ $transaksi->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada barang masuk hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Card Tabel Barang Keluar Hari Ini --}}
        <div class="col-md-6">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">Barang Keluar Hari Ini</h3>
                </div>
                 <div class="card-body table-responsive p-0" style="height: 300px;">
                    <table class="table table-head-fixed text-nowrap">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                             @forelse ($transaksiKeluarHariIni as $transaksi)
                                <tr>
                                    <td>{{ $transaksi->barang->nama }}</td>
                                    <td>{{ $transaksi->qty }} {{ $transaksi->barang->satuan }}</td>
                                    <td>{{ $transaksi->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada barang keluar hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable untuk tabel stok utama
            $('#tabel-stok').DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                scrollY: "400px", // Aktifkan jika ingin tinggi tabel tetap
                scrollCollapse: true,
                paging: true,
                ordering: false,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json"
                },
            });
        });
    </script>
@stop