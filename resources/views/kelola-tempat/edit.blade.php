@extends('adminlte::page')
@section('title', 'Edit Tempat')
@section('content_header')
    <h1>Edit Tempat: {{ $tempat->nama }}</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('tempat.update', $tempat->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="nama">Nama Tempat</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" id="nama" value="{{ old('nama', $tempat->nama) }}" required>
                    @error('nama')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('tempat.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop