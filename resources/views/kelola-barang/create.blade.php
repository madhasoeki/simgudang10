@extends('adminlte::page')

@section('title', 'Tambah Barang Baru')

@section('content_header')
    <h1>Tambah Barang Baru</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('barang.store') }}" method="POST">
                @csrf
                
                {{-- Field Kode Barang --}}
                <div class="form-group">
                    <label for="kode">Kode Barang</label>
                    <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror" id="kode" value="{{ old('kode') }}" required>
                    @error('kode')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                {{-- Field Nama Barang --}}
                <div class="form-group">
                    <label for="nama">Nama Barang</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama') }}" required>
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
                        <option value="">-- Pilih Satuan --</option>
                        @foreach ($satuanOptions as $satuan)
                            <option value="{{ $satuan }}" {{ old('satuan') == $satuan ? 'selected' : '' }}>{{ $satuan }}</option>
                        @endforeach
                    </select>
                    @error('satuan')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('barang.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop