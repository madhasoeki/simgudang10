@extends('adminlte::page')

{{-- Aktifkan plugin Select2 --}}
@section('plugins.Select2', true)

@section('title', 'Edit Barang')

@section('content_header')
    <h1>Edit Barang: <small>{{ $barang->nama }}</small></h1> {{-- Small untuk nama barang --}}
@stop

@section('content')
<div class="card card-warning"> {{-- Tambahkan card-warning --}}
    <div class="card-header">
        <h3 class="card-title">Formulir Edit Barang</h3> {{-- Judul di card header --}}
    </div>
    <form action="{{ route('barang.update', $barang->kode) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Field Kode Barang (readonly) --}}
            <div class="form-group">
                <label for="kode">Kode Barang</label>
                <input type="text" name="kode" class="form-control" id="kode" value="{{ $barang->kode }}" readonly>
            </div>

            {{-- Field Nama Barang --}}
            <div class="form-group">
                <label for="nama">Nama Barang</label>
                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama', $barang->nama) }}" placeholder="Masukkan nama barang" required>
                @error('nama')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Field Satuan --}}
            <div class="form-group">
                <label for="satuan">Satuan</label>
                <select name="satuan" class="form-control @error('satuan') is-invalid @enderror" id="satuan" required>
                    <option value="">-- Pilih Satuan --</option> {{-- Tambahkan placeholder default --}}
                    @foreach ($satuanOptions as $opsiSatuan) {{-- Ganti nama variabel agar tidak konflik --}}
                        <option value="{{ $opsiSatuan }}" {{ old('satuan', $barang->satuan) == $opsiSatuan ? 'selected' : '' }}>{{ $opsiSatuan }}</option>
                    @endforeach
                </select>
                @error('satuan')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer"> {{-- Pindahkan tombol ke card-footer --}}
            <button type="submit" class="btn btn-warning">Simpan Perubahan</button> {{-- Ganti warna tombol --}}
            <a href="{{ route('barang.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop

@section('css')
{{-- Tambahkan CSS ini jika Select2 masih belum rapi setelah opsi width di JS --}}
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
        // Inisialisasi Select2 untuk dropdown Satuan
        $('#satuan').select2({
            placeholder: "-- Pilih Satuan --",
            allowClear: true,
            width: '100%' // Atur lebar agar konsisten
        });
    });
</script>
@stop