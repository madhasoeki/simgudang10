@extends('adminlte::page')

@section('title', 'Edit Tempat')

@section('content_header')
    <h1>Edit Tempat: <small>{{ $tempat->nama }}</small></h1> {{-- Menggunakan <small> untuk nama --}}
@stop

@section('content')
<div class="card card-warning"> {{-- Tambahkan class card-warning --}}
    <div class="card-header"> {{-- Tambahkan card-header --}}
        <h3 class="card-title">Formulir Edit Tempat</h3>
    </div>
    <form action="{{ route('tempat.update', $tempat->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="nama">Nama Tempat</label>
                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama', $tempat->nama) }}" placeholder="Masukkan nama tempat" required>
                @error('nama')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
        <div class="card-footer"> {{-- Pindahkan tombol ke card-footer --}}
            <button type="submit" class="btn btn-warning">Simpan Perubahan</button> {{-- Ganti warna tombol --}}
            <a href="{{ route('tempat.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop