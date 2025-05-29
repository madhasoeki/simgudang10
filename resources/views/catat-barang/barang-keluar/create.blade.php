@extends('adminlte::page')

{{-- Aktifkan plugin Select2 --}}
@section('plugins.Select2', true)

@section('title', 'Catat Barang Keluar Baru')

@section('content_header')
    <h1>Catat Barang Keluar Baru</h1>
@stop

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Formulir Barang Keluar</h3>
    </div>
    <form action="{{ route('barang-keluar.store') }}" method="POST" id="form-barang-keluar">
        @csrf
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong> Terdapat masalah dengan input Anda.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    {{-- Field Pilih Barang --}}
                    <div class="form-group">
                        <label for="barang_kode">Nama Barang <span class="text-danger">*</span></label>
                        <select name="barang_kode" id="barang_kode" class="form-control @error('barang_kode') is-invalid @enderror" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($barangs as $barang)
                                <option value="{{ $barang->kode }}" data-nama="{{ $barang->nama }}" {{ old('barang_kode') == $barang->kode ? 'selected' : '' }}>
                                    {{ $barang->kode }} - {{ $barang->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('barang_kode') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Field Pilih Harga (akan diisi dinamis) --}}
                    <div class="form-group">
                        <label for="harga">Harga Barang (per Satuan) <span class="text-danger">*</span></label>
                        <select name="harga" id="harga" class="form-control @error('harga') is-invalid @enderror" required disabled>
                            <option value="">-- Pilih Barang Terlebih Dahulu --</option>
                            {{-- Opsi harga akan diisi oleh JavaScript --}}
                        </select>
                        @error('harga') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {{-- Field Kuantitas --}}
                    <div class="form-group">
                        <label for="qty">Kuantitas Keluar <span class="text-danger">*</span></label>
                        <input type="number" name="qty" id="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty') }}" placeholder="Masukkan kuantitas" required disabled>
                        <small id="stok-tersedia-info" class="form-text text-muted"></small> {{-- Info stok tersedia --}}
                        @error('qty') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Field Pilih Tempat Tujuan --}}
                    <div class="form-group">
                        <label for="tempat_id">Tempat Tujuan <span class="text-danger">*</span></label>
                        <select name="tempat_id" id="tempat_id" class="form-control @error('tempat_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Tempat Tujuan --</option>
                            @foreach ($tempats as $tempat)
                                <option value="{{ $tempat->id }}" {{ old('tempat_id') == $tempat->id ? 'selected' : '' }}>
                                    {{ $tempat->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('tempat_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    {{-- Field Tanggal Keluar --}}
                    <div class="form-group">
                        <label for="tanggal">Tanggal Keluar <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                        @error('tanggal') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                 <div class="col-md-6">
                    {{-- Field Keterangan (Opsional) --}}
                    <div class="form-group">
                        <label for="keterangan">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="3" placeholder="Masukkan keterangan jika ada">{{ old('keterangan') }}</textarea>
                        @error('keterangan') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan Barang Keluar</button>
            <a href="{{ route('barang-keluar.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop

@section('css')
<style>
    /* Pastikan Select2 memiliki lebar yang konsisten */
    .form-group .select2-container {
        width: 100% !important;
    }
    .select2-selection--single {
        height: calc(2.25rem + 2px) !important; /* Samakan tinggi dengan form-control Bootstrap */
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
    // Inisialisasi Select2
    $('#barang_kode').select2({
        placeholder: "-- Pilih Barang --",
        allowClear: true
    });
    $('#tempat_id').select2({
        placeholder: "-- Pilih Tempat Tujuan --",
        allowClear: true
    });
    $('#harga').select2({
        placeholder: "-- Pilih Barang Terlebih Dahulu --",
        allowClear: true
    });

    // Event listener ketika pilihan barang berubah
    $('#barang_kode').on('change', function() {
        var barangKode = $(this).val();
        var hargaSelect = $('#harga');
        var qtyInput = $('#qty');
        var stokInfo = $('#stok-tersedia-info');

        hargaSelect.empty().append('<option value="">Memuat harga...</option>').prop('disabled', true);
        qtyInput.prop('disabled', true).val('');
        stokInfo.text('');

        if (barangKode) {
            // Ambil data harga dan stok dari server
            $.ajax({
                url: '{{ url("barang-keluar/get-harga-stok") }}/' + barangKode,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    hargaSelect.empty().append('<option value="">-- Pilih Harga --</option>');
                    if (data.length > 0) {
                        $.each(data, function(key, value) {
                            hargaSelect.append('<option value="' + value.harga + '" data-stok="' + value.jumlah + '">' + 
                                'Rp ' + parseInt(value.harga).toLocaleString('id-ID') + ' (Stok: ' + value.jumlah + ')' +
                            '</option>');
                        });
                        hargaSelect.prop('disabled', false);
                    } else {
                        hargaSelect.append('<option value="">Stok kosong/harga tidak tersedia</option>');
                    }
                },
                error: function() {
                    hargaSelect.empty().append('<option value="">Gagal memuat harga</option>');
                    alert('Gagal mengambil data harga. Silakan coba lagi.');
                }
            });
        } else {
             hargaSelect.empty().append('<option value="">-- Pilih Barang Terlebih Dahulu --</option>');
        }
    });

    // Event listener ketika pilihan harga berubah
    $('#harga').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var stokTersedia = selectedOption.data('stok');
        var qtyInput = $('#qty');
        var stokInfo = $('#stok-tersedia-info');

        if (stokTersedia !== undefined && stokTersedia > 0) {
            qtyInput.prop('disabled', false).attr('max', stokTersedia).attr('placeholder', 'Maks. ' + stokTersedia);
            stokInfo.text('Stok tersedia untuk harga ini: ' + stokTersedia);
        } else {
            qtyInput.prop('disabled', true).val('').attr('placeholder', 'Pilih harga dengan stok tersedia');
            stokInfo.text('Pilih harga dengan stok yang tersedia.');
        }
    });

    // Validasi kuantitas saat form disubmit (opsional, tambahan)
    $('#form-barang-keluar').on('submit', function(e){
        var qtyInput = $('#qty');
        var qtyValue = parseInt(qtyInput.val());
        var maxStok = parseInt(qtyInput.attr('max'));

        if(qtyValue > maxStok){
            e.preventDefault(); // Mencegah form submit
            Swal.fire({
                icon: 'error',
                title: 'Kuantitas Melebihi Stok!',
                text: 'Kuantitas yang Anda masukkan (' + qtyValue + ') melebihi stok yang tersedia (' + maxStok + ') untuk harga ini.',
            });
        }
         if(qtyValue <= 0){
            e.preventDefault(); 
            Swal.fire({
                icon: 'error',
                title: 'Kuantitas Tidak Valid!',
                text: 'Kuantitas barang keluar harus lebih dari 0.',
            });
        }
    });
});
</script>
@stop