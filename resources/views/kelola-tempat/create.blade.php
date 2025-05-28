@extends('adminlte::page')

@section('title', 'Tambah Tempat Baru')

@section('content_header')
    <h1>Tambah Tempat Baru</h1>
@stop

@section('content')
<div class="card card-primary"> {{-- Tambahkan class card-primary --}}
    <div class="card-header"> {{-- Tambahkan card-header --}}
        <h3 class="card-title">Formulir Tambah Tempat</h3>
    </div>
    <form action="{{ route('tempat.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="nama">Nama Tempat</label>
                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama') }}" placeholder="Masukkan nama tempat" required>
                @error('nama')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
        <div class="card-footer"> {{-- Pindahkan tombol ke card-footer --}}
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('tempat.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop