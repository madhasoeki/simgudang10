@extends('adminlte::page')

@section('title', 'Edit Barang')

@section('content_header')
    {{-- Ubah Judul --}}
    <h1>Edit Barang: {{ $barang->nama }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            {{-- 1. Ubah action form ke route 'update' dan kirimkan kode barang --}}
             {{-- @dd($barang) --}}
            <form action="{{ route('barang.update', $barang->kode) }}" method="POST">
                @csrf
                @method('PUT') {{-- 2. Tambahkan method spoofing untuk PUT/PATCH --}}

                {{-- Field Kode Barang (dibuat readonly karena primary key tidak boleh diubah) --}}
                <div class="form-group">
                    <label for="kode">Kode Barang</label>
                    <input type="text" name="kode" class="form-control" id="kode" value="{{ $barang->kode }}" readonly>
                </div>

                {{-- Field Nama Barang (value diisi dengan data yang ada) --}}
                <div class="form-group">
                    <label for="nama">Nama Barang</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama', $barang->nama) }}" required>
                    @error('nama')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                {{-- Field Satuan (pilih opsi yang sesuai dengan data yang ada) --}}
                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select name="satuan" class="form-control @error('satuan') is-invalid @enderror" id="satuan" required>
                        @foreach ($satuanOptions as $satuan)
                            <option value="{{ $satuan }}" {{ old('satuan', $barang->satuan) == $satuan ? 'selected' : '' }}>{{ $satuan }}</option>
                        @endforeach
                    </select>
                    @error('satuan')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('barang.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop