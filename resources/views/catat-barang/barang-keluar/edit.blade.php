@extends('adminlte::page')

{{-- Aktifkan plugin Select2 --}}
@section('plugins.Select2', true)

@section('title', 'Edit Catatan Barang Keluar')

@section('content_header')
    <h1>Edit Catatan Barang Keluar</h1>
@stop

@section('content')
<div class="card card-warning"> {{-- Warna card untuk edit --}}
    <div class="card-header">
        <h3 class="card-title">Formulir Edit Barang Keluar</h3>
    </div>
    <form action="{{ route('barang-keluar.update', $barangKeluar->id) }}" method="POST" id="form-barang-keluar-edit">
        @csrf
        @method('PUT') {{-- Method untuk update --}}
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
                        <label for="barang_kode_edit">Nama Barang <span class="text-danger">*</span></label>
                        <select name="barang_kode" id="barang_kode_edit" class="form-control @error('barang_kode') is-invalid @enderror" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($barangs as $barang)
                                <option value="{{ $barang->kode }}" data-nama="{{ $barang->nama }}" 
                                    {{ old('barang_kode', $barangKeluar->barang_kode) == $barang->kode ? 'selected' : '' }}>
                                    {{ $barang->kode }} - {{ $barang->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('barang_kode') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Field Pilih Harga (diisi dinamis, tapi ada nilai awal) --}}
                    <div class="form-group">
                        <label for="harga_edit">Harga Barang (per Satuan) <span class="text-danger">*</span></label>
                        <select name="harga" id="harga_edit" class="form-control @error('harga') is-invalid @enderror" required>
                            <option value="">-- Pilih Barang Terlebih Dahulu --</option>
                            {{-- Opsi harga akan diisi oleh JavaScript, tapi kita sediakan nilai awal --}}
                            @foreach ($stokHargaTersedia as $stok)
                                <option value="{{ $stok->harga }}" data-stok="{{ $stok->jumlah }}"
                                    {{ old('harga', $barangKeluar->harga) == $stok->harga ? 'selected' : '' }}>
                                    Rp {{ number_format($stok->harga, 0, ',', '.') }} (Stok: {{ $stok->jumlah }})
                                </option>
                            @endforeach
                        </select>
                        @error('harga') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {{-- Field Kuantitas --}}
                    <div class="form-group">
                        <label for="qty_edit">Kuantitas Keluar <span class="text-danger">*</span></label>
                        <input type="number" name="qty" id="qty_edit" 
                               class="form-control @error('qty') is-invalid @enderror" 
                               value="{{ old('qty', $barangKeluar->qty) }}" placeholder="Masukkan kuantitas" required>
                        <small id="stok-tersedia-info-edit" class="form-text text-muted"></small>
                        @error('qty') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Field Pilih Tempat Tujuan --}}
                    <div class="form-group">
                        <label for="tempat_id_edit">Tempat Tujuan <span class="text-danger">*</span></label>
                        <select name="tempat_id" id="tempat_id_edit" class="form-control @error('tempat_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Tempat Tujuan --</option>
                            @foreach ($tempats as $tempat)
                                <option value="{{ $tempat->id }}" 
                                    {{ old('tempat_id', $barangKeluar->tempat_id) == $tempat->id ? 'selected' : '' }}>
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
                        <label for="tanggal_edit">Tanggal Keluar <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal_edit" 
                               class="form-control @error('tanggal') is-invalid @enderror" 
                               value="{{ old('tanggal', $barangKeluar->tanggal ? \Carbon\Carbon::parse($barangKeluar->tanggal)->format('Y-m-d') : '') }}" required>
                        @error('tanggal') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                 <div class="col-md-6">
                    {{-- Field Keterangan (Opsional) --}}
                    <div class="form-group">
                        <label for="keterangan_edit">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="keterangan_edit" 
                                  class="form-control @error('keterangan') is-invalid @enderror" 
                                  rows="3" placeholder="Masukkan keterangan jika ada">{{ old('keterangan', $barangKeluar->keterangan) }}</textarea>
                        @error('keterangan') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Update Catatan</button>
            <a href="{{ route('barang-keluar.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@stop

@section('css')
<style>
    /* CSS untuk Select2 agar lebarnya konsisten */
    .form-group .select2-container { width: 100% !important; }
    .select2-selection--single { height: calc(2.25rem + 2px) !important; padding: .375rem .75rem !important; line-height: 1.5 !important; }
    .select2-selection__arrow { height: calc(2.25rem + 2px) !important; }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Fungsi untuk memuat harga berdasarkan barang_kode
    function loadHargaStok(barangKode, hargaSelectElement, qtyInputElement, stokInfoElement, selectedHarga = null) {
        hargaSelectElement.empty().append('<option value="">Memuat harga...</option>').prop('disabled', true);
        qtyInputElement.prop('disabled', true).val('');
        stokInfoElement.text('');

        if (barangKode) {
            $.ajax({
                url: '{{ url("barang-keluar/get-harga-stok") }}/' + barangKode,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    hargaSelectElement.empty().append('<option value="">-- Pilih Harga --</option>');
                    if (data.length > 0) {
                        var currentBarangKeluarHarga = "{{ $barangKeluar->harga }}"; // Ambil harga saat ini dari record
                        var hargaSaatIniAdaDiStok = false;

                        $.each(data, function(key, value) {
                            // Cek apakah harga saat ini dari record yang diedit ada di daftar stok
                            if (value.harga == currentBarangKeluarHarga) {
                                hargaSaatIniAdaDiStok = true;
                            }
                            // Tambahkan ke dropdown
                            var optionText = 'Rp ' + parseInt(value.harga).toLocaleString('id-ID') + 
                                             ' (Stok: ' + value.jumlah + ')';
                            var option = new Option(optionText, value.harga, false, 
                                                    (selectedHarga == value.harga || (!selectedHarga && currentBarangKeluarHarga == value.harga)));
                            $(option).data('stok', value.jumlah);
                            hargaSelectElement.append(option);
                        });

                        // Jika harga saat ini dari record yang diedit tidak ada di stok aktif (stok > 0),
                        // tambahkan sebagai opsi khusus agar bisa dipilih.
                        // Ini penting jika pengguna tidak mengubah harga, tapi stok untuk harga itu sudah habis oleh transaksi lain.
                        // Namun, karena kita sudah menggunakan orWhere di controller, ini mungkin sudah ter-handle.
                        // Kita akan andalkan data dari $stokHargaTersedia untuk initial load.
                        
                        // Untuk initial load, kita pakai $stokHargaTersedia dari PHP.
                        // Kita hanya perlu re-select jika barang_kode berubah.
                        if (selectedHarga) {
                            hargaSelectElement.val(selectedHarga).trigger('change');
                        } else {
                            // Saat pertama kali load atau jika barang kode berubah dan tidak ada old input harga
                            hargaSelectElement.val("{{ old('harga', $barangKeluar->harga) }}").trigger('change');
                        }
                        hargaSelectElement.prop('disabled', false);

                    } else {
                        hargaSelectElement.append('<option value="">Stok kosong/harga tidak tersedia</option>');
                    }
                },
                error: function() {
                    hargaSelectElement.empty().append('<option value="">Gagal memuat harga</option>');
                    alert('Gagal mengambil data harga. Silakan coba lagi.');
                }
            });
        } else {
            hargaSelectElement.empty().append('<option value="">-- Pilih Barang Terlebih Dahulu --</option>');
        }
    }

    // Inisialisasi Select2
    $('#barang_kode_edit').select2({ placeholder: "-- Pilih Barang --", allowClear: true });
    $('#tempat_id_edit').select2({ placeholder: "-- Pilih Tempat Tujuan --", allowClear: true });
    $('#harga_edit').select2({ placeholder: "-- Pilih Harga --", allowClear: true });

    // Initial load data stok dan harga jika barang_kode sudah terpilih (dari old input atau data edit)
    var initialBarangKode = $('#barang_kode_edit').val();
    var initialHarga = "{{ old('harga', $barangKeluar->harga) }}"; 
    if(initialBarangKode){
        // Kita tidak perlu memanggil loadHargaStok di sini karena dropdown harga sudah di-populate dari PHP ($stokHargaTersedia)
        // Cukup trigger change pada harga jika sudah ada nilai awal untuk mengupdate info stok Qty
        if(initialHarga){
             // Set info stok berdasarkan harga awal yang sudah terpilih
            var selectedOptionHarga = $('#harga_edit').find('option[value="' + initialHarga + '"]');
            if(selectedOptionHarga.length > 0){
                var stokAwal = selectedOptionHarga.data('stok');
                var qtyAwal = parseInt($('#qty_edit').val());
                var stokTersediaInfoAwal = stokAwal; // Stok yang ada di record tersebut
                // Untuk edit, stok tersedia adalah stok di DB + qty dari record ini
                // Karena qty ini akan dikembalikan dulu sebelum dikurangi lagi.
                 $('#stok-tersedia-info-edit').text('Stok tersedia untuk harga ini: ' + (stokAwal + qtyAwal) ); 
                 $('#qty_edit').attr('max', (stokAwal + qtyAwal) ).prop('disabled', false);
            }
        }
    }


    // Event listener ketika pilihan barang berubah
    $('#barang_kode_edit').on('change', function() {
        var barangKode = $(this).val();
        // Panggil fungsi untuk memuat harga, dengan parameter null untuk selectedHarga
        // agar memilih nilai default atau yang pertama tersedia.
        loadHargaStok(barangKode, $('#harga_edit'), $('#qty_edit'), $('#stok-tersedia-info-edit'), null);
    });

    // Event listener ketika pilihan harga berubah
    $('#harga_edit').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var stokTersedia = selectedOption.data('stok'); // Stok aktual di DB untuk kombinasi barang & harga ini
        var qtyInput = $('#qty_edit');
        var stokInfo = $('#stok-tersedia-info-edit');
        var qtySaatIni = parseInt("{{ $barangKeluar->qty }}"); // Qty dari record yang sedang diedit
        var hargaSaatIni = "{{ $barangKeluar->harga }}";
        var barangKodeSaatIni = "{{ $barangKeluar->barang_kode }}";
        
        var hargaDipilih = $(this).val();
        var barangKodeDipilih = $('#barang_kode_edit').val();

        if (stokTersedia !== undefined) {
            var stokMaksimalUntukInput = stokTersedia;
            // Jika barang dan harga yang dipilih SAMA dengan barang dan harga awal record ini,
            // maka stok yang bisa dikeluarkan adalah stokTersedia + qtySaatIni (karena qtySaatIni akan dikembalikan dulu)
            if (barangKodeDipilih === barangKodeSaatIni && hargaDipilih === hargaSaatIni) {
                stokMaksimalUntukInput += qtySaatIni;
            }

            qtyInput.prop('disabled', false).attr('max', stokMaksimalUntukInput).attr('placeholder', 'Maks. ' + stokMaksimalUntukInput);
            stokInfo.text('Stok tersedia untuk harga ini: ' + stokMaksimalUntukInput + 
                          ( (barangKodeDipilih === barangKodeSaatIni && hargaDipilih === hargaSaatIni) ? ' (termasuk '+qtySaatIni+' dari transaksi ini)' : '' ) );
        } else {
            qtyInput.prop('disabled', true).val('').attr('placeholder', 'Pilih harga dengan stok tersedia');
            stokInfo.text('Pilih harga dengan stok yang tersedia.');
        }
    });
     // Trigger change pada harga_edit saat load untuk set info stok awal jika harga sudah terpilih
    if ($('#harga_edit').val()) {
        $('#harga_edit').trigger('change');
    }


    // Validasi kuantitas saat form disubmit
    $('#form-barang-keluar-edit').on('submit', function(e){
        var qtyInput = $('#qty_edit');
        var qtyValue = parseInt(qtyInput.val());
        var maxStok = parseInt(qtyInput.attr('max'));

        if (qtyValue <= 0) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Kuantitas Tidak Valid!', text: 'Kuantitas barang keluar harus lebih dari 0.' });
            return;
        }
        if (qtyValue > maxStok) {
            e.preventDefault(); 
            Swal.fire({
                icon: 'error', title: 'Kuantitas Melebihi Stok!',
                text: 'Kuantitas yang Anda masukkan (' + qtyValue + ') melebihi stok yang tersedia (' + maxStok + ') untuk kombinasi barang dan harga ini.',
            });
        }
    });
});
</script>
@stop