@extends('adminlte::page')

{{-- Aktifkan plugin Select2 jika Anda menggunakannya --}}
@section('plugins.Select2', true)

@section('title', 'Edit Barang Masuk')

@section('content_header')
    <h1>Edit Data Barang Masuk</h1>
@stop

@section('content')
<div class="card card-warning"> {{-- Ganti warna card menjadi warning untuk edit --}}
    <div class="card-header">
        <h3 class="card-title">Formulir Edit Barang Masuk</h3>
    </div>
    {{-- Form akan menunjuk ke route update dengan method PUT --}}
    <form action="{{ route('barang-masuk.update', $barangMasuk->id) }}" method="POST">
        @csrf
        @method('PUT') {{-- Penting untuk operasi update --}}
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

            <div class="form-group">
                <label for="barang_kode">Nama Barang</label>
                <select name="barang_kode" id="barang_kode" class="form-control @error('barang_kode') is-invalid @enderror">
                    <option value="">-- Pilih Barang --</option>
                    @foreach ($barangs as $barang)
                        <option value="{{ $barang->kode }}" 
                                {{ old('barang_kode', $barangMasuk->barang_kode) == $barang->kode ? 'selected' : '' }}>
                            {{ $barang->kode }} - {{ $barang->nama }}
                        </option>
                    @endforeach
                </select>
                @error('barang_kode') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="qty">Kuantitas (Qty)</label>
                <input type="number" name="qty" id="qty" 
                       class="form-control @error('qty') is-invalid @enderror" 
                       value="{{ old('qty', $barangMasuk->qty) }}" placeholder="Masukkan kuantitas">
                @error('qty') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="harga">Harga Satuan</label>
                <input type="number" name="harga" id="harga" 
                       class="form-control @error('harga') is-invalid @enderror" 
                       value="{{ old('harga', $barangMasuk->harga) }}" placeholder="Masukkan harga satuan">
                @error('harga') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="tanggal">Tanggal Masuk</label>
                <input type="date" name="tanggal" id="tanggal_edit" {{-- Pastikan ID unik jika perlu --}}
                    class="form-control @error('tanggal') is-invalid @enderror" 
                    value="{{ old('tanggal', $barangMasuk->tanggal ? \Carbon\Carbon::parse($barangMasuk->tanggal)->format('Y-m-d') : $today) }}"
                    max="{{ $today }}" {{-- Batas maksimal adalah hari ini --}}
                    required>
                @error('tanggal') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Update Data</button>
            <a href="{{ route('barang-masuk.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop

@section('css')
<style>
    /* Target spesifik ke Select2 di dalam form-group AdminLTE */
    .form-group .select2-container {
        width: 100% !important; /* Paksa lebar 100% */
    }
    /* Pastikan elemen Select2 yang digenerate juga mengikuti */
    .select2-selection--single {
        height: calc(2.25rem + 2px) !important; /* Samakan tinggi dengan form-control Bootstrap */
        padding: .375rem .75rem !important; /* Samakan padding */
        line-height: 1.5 !important; /* Samakan line-height */
    }
    .select2-selection__arrow {
        height: calc(2.25rem + 2px) !important; /* Samakan tinggi panah */
    }
</style>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Inisialisasi Select2 jika Anda menggunakannya
        $('#barang_kode').select2({
            placeholder: "-- Pilih Barang --",
            allowClear: true
        });
    });
</script>
@stop