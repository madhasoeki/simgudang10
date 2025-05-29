@extends('adminlte::page')

@section('title', 'Laporan Stok Opname')

@section('plugins.DateRangePicker', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1 class="m-0 text-dark">Laporan Stok Opname</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-default" id="date-range-btn">
                                <i class="far fa-calendar-alt"></i>&nbsp;
                                <span></span>&nbsp;
                                <i class="fa fa-caret-down"></i>
                            </button>
                        </div>
                        <div class="col-md text-md-right">
                            <div class="btn-toolbar" role="toolbar" style="justify-content: flex-end;">
                                <div class="btn-group" role="group">
                                     <form id="form-refresh" action="{{ route('opname.refresh') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-sync"></i> Segarkan Data
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-hover table-bordered table-stripped" id="opname-table" style="width:100%;">
                        <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Stok Awal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Stok Sistem</th>
                            <th style="min-width: 120px;">Stok Lapangan</th>
                            <th>Selisih</th>
                            <th style="min-width: 150px;">Keterangan</th>
                            <th style="min-width: 180px;">Aksi</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Notifikasi Toast
            @if (session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; } });
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif
            @if (session('error'))
                 const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 5000, timerProgressBar: true, didOpen: (toast) => { toast.onmouseenter = Swal.stopTimer; toast.onmouseleave = Swal.resumeTimer; } });
                Toast.fire({ icon: 'error', title: '{{ session('error') }}' });
            @endif

            // --- Inisialisasi Date Range Picker ---
            function getOpnamePeriodForDate(refDateInput) {
                let refDate = moment(refDateInput);
                if (!refDate.isValid()) { refDate = moment(); }
                let startPeriod, endPeriod;
                if (refDate.date() >= 26) {
                    startPeriod = refDate.clone().date(26);
                    endPeriod = refDate.clone().add(1, 'month').date(25);
                } else {
                    startPeriod = refDate.clone().subtract(1, 'month').date(26);
                    endPeriod = refDate.clone().date(25);
                }
                return { start: startPeriod, end: endPeriod };
            }

            let currentPeriod = getOpnamePeriodForDate(moment());
            let lastPeriod = getOpnamePeriodForDate(moment().subtract(1, 'month'));
            let sixMonthsAgoRef = currentPeriod.start.clone().subtract(5, 'months');
            let sixMonthsAgoStart = getOpnamePeriodForDate(sixMonthsAgoRef).start;
            let startOfYearOpnamePeriod = getOpnamePeriodForDate(moment().startOf('year'));
            let maxStartDate = startOfYearOpnamePeriod.start.clone();

            function cb(start, end, label) {
                let textToShow = label && label !== "Kustom" ? label : start.format('D MMM YY') + ' - ' + end.format('D MMM YY');
                $('#date-range-btn span').html(textToShow);
            }

            $('#date-range-btn').daterangepicker({
                startDate: currentPeriod.start, endDate: currentPeriod.end, alwaysShowCalendars: true,
                ranges: {
                   'Periode Ini': [currentPeriod.start, currentPeriod.end],
                   'Periode Lalu': [lastPeriod.start, lastPeriod.end],
                   '6 Bulan Ini': [sixMonthsAgoStart, currentPeriod.end],
                   'Tahun Ini': [startOfYearOpnamePeriod.start, currentPeriod.end],
                   'Maksimal': [maxStartDate, currentPeriod.end],
                },
                locale: {"format": "DD/MM/YYYY", "applyLabel": "Terapkan", "cancelLabel": "Batal", "customRangeLabel": "Kustom", "daysOfWeek": ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"], "monthNames": ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],}
            }, cb);
            cb(currentPeriod.start, currentPeriod.end, "Periode Ini");

            // --- DataTables ---
            let opnameTable = $('#opname-table').DataTable({
                processing: true, serverSide: true, ordering: false,
                language: { url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/id.json" },
                ajax: {
                    url: "{{ route('opname.data') }}", type: "GET",
                    data: function (d) {
                        let picker = $('#date-range-btn').data('daterangepicker');
                        d.start_date = picker.startDate && picker.startDate.isValid() ? picker.startDate.format('YYYY-MM-DD') : currentPeriod.start.format('YYYY-MM-DD');
                        d.end_date = picker.endDate && picker.endDate.isValid() ? picker.endDate.format('YYYY-MM-DD') : currentPeriod.end.format('YYYY-MM-DD');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("DataTables AJAX Error:", { status: jqXHR.status, statusText: jqXHR.statusText, responseText: jqXHR.responseText, textStatus: textStatus, errorThrown: errorThrown });
                        Swal.fire({ icon: 'error', title: 'Gagal Memuat Data', text: 'Terjadi masalah saat mengambil data opname. Coba muat ulang halaman.', footer: '<small>Detail error ada di console browser (F12).</small>' });
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'barang.nama', name: 'barang.nama'},
                    {data: 'stock_awal', name: 'stock_awal', orderable: false},
                    {data: 'total_masuk', name: 'total_masuk', orderable: false},
                    {data: 'total_keluar', name: 'total_keluar', orderable: false},
                    {data: 'stock_total', name: 'stock_total', orderable: false},
                    {data: 'total_lapangan', name: 'total_lapangan', orderable: false, searchable: false},
                    {data: 'selisih', name: 'selisih', orderable: false, searchable: false},
                    {data: 'keterangan', name: 'keterangan', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center'},
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"fB>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{ extend: 'excelHtml5', title: 'Laporan Stok Opname', text: '<i class="fa fa-fw fa-file-excel"></i> Excel', className: 'btn btn-success ml-2' }]
            });
            
            $('#date-range-btn').on('apply.daterangepicker', function(ev, picker) {
                cb(picker.startDate, picker.endDate, picker.chosenLabel);
                opnameTable.ajax.reload();
            });

            $('#form-refresh').on('submit', function(e) {
                e.preventDefault(); var form = this;
                Swal.fire({
                    title: 'Anda Yakin?', text: "Data opname yang belum diapprove akan dikalkulasi ulang.", icon: 'question',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Segarkan!', cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { form.submit(); } });
            });

            // --- LOGIKA UNTUK EDIT INLINE ---
            function switchToViewMode(row) {
                row.find('.editable-cell').each(function() {
                    let originalValue = $(this).data('original-value');
                    let field = $(this).data('field');
                    $(this).find('.editable-input').hide().val(originalValue);
                    $(this).find('.editable-text').text(field === 'keterangan' && (originalValue === '' || originalValue === null) ? '-' : originalValue).show();
                });
                row.find('.btn-edit-row').show();
                row.find('.btn-save-row, .btn-cancel-row').hide();
                row.removeClass('editing-mode');
            }

            $('#opname-table tbody').on('click', '.btn-edit-row', function() {
                let row = $(this).closest('tr');
                // Jika ada baris lain yang sedang diedit, batalkan dulu
                $('#opname-table tbody tr.editing-mode').each(function() {
                    if (!$(this).is(row)) {
                        switchToViewMode($(this));
                    }
                });
                row.addClass('editing-mode');
                row.find('.editable-cell').each(function() {
                    $(this).find('.editable-text').hide();
                    $(this).find('.editable-input').show().first().focus();
                });
                $(this).hide();
                row.find('.btn-save-row, .btn-cancel-row').show();
            });

            $('#opname-table tbody').on('click', '.btn-cancel-row', function() {
                switchToViewMode($(this).closest('tr'));
            });
            
            // Event handler untuk tombol SIMPAN ROW (menggunakan AJAX)
            $('#opname-table tbody').on('click', '.btn-save-row', function() {
                let opnameId = $(this).data('opname-id');
                let row = $(this).closest('tr');
                let totalLapanganVal = row.find("div[data-field='total_lapangan'] .editable-input").val();
                let keteranganVal = row.find("div[data-field='keterangan'] .editable-input").val();
                let stockSistemText = row.find("td:eq(5)").text().trim(); // Kolom ke-6 (index 5)
                let stockSistemVal = parseFloat(stockSistemText);

                totalLapanganVal = totalLapanganVal.trim();
                if (totalLapanganVal === '' || isNaN(parseInt(totalLapanganVal)) || parseInt(totalLapanganVal) < 0) {
                    Swal.fire('Error!', 'Stok Lapangan harus berupa angka integer positif atau nol.', 'error');
                    return;
                }
                let parsedTotalLapangan = parseInt(totalLapanganVal);
                let selisihClient = parsedTotalLapangan - stockSistemVal;

                if (selisihClient != 0 && keteranganVal.trim() === '') {
                    if (parsedTotalLapangan != 0 || (parsedTotalLapangan == 0 && stockSistemVal == 0) ) {
                        Swal.fire('Peringatan!', 'Keterangan wajib diisi jika Stok Lapangan berbeda dengan Stok Sistem.', 'warning');
                        row.find("div[data-field='keterangan'] .editable-input").focus();
                        return; 
                    }
                }

                let dataToSend = {
                    _method: 'PUT', _token: "{{ csrf_token() }}",
                    total_lapangan: parsedTotalLapangan, keterangan: keteranganVal.trim()
                };

                let saveButton = $(this);
                saveButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                $.ajax({
                    url: `/opname/update/${opnameId}`, type: 'POST', data: dataToSend,
                    success: function(response) {
                        // Karena controller update mengembalikan JSON jika AJAX
                        Toast.fire({ icon: 'success', title: response.message || 'Data berhasil disimpan.' });
                        opnameTable.ajax.reload(null, false); 
                        // switchToViewMode(row); // Tidak perlu, karena DataTables reload akan render ulang
                    },
                    error: function(xhr) {
                        let errorMessage = 'Gagal menyimpan data.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).map(e => e[0]).join('<br>');
                            } else if (xhr.responseJSON.message) { errorMessage = xhr.responseJSON.message; }
                        }
                        Swal.fire('Error!', errorMessage, 'error');
                    },
                    complete: function() {
                        saveButton.prop('disabled', false).html('<i class="fa fa-save"></i>');
                         // Pastikan baris kembali ke mode view jika tidak ada error parah
                        if(!saveButton.closest('tr').hasClass('editing-mode')) { // jika belum direload ajax
                            switchToViewMode(row);
                        }
                    }
                });
            });

            // Event handler untuk tombol APPROVE (Form Submit)
            $('#opname-table tbody').on('click', '.btn-confirm-approve', function(e) {
                e.preventDefault();
                let opnameId = $(this).data('opname-id');
                let targetForm = $('#form-approve-' + opnameId);
                let row = $(this).closest('tr');

                let totalLapanganCell = row.find("div[data-field='total_lapangan']");
                let totalLapanganVal = totalLapanganCell.find('.editable-input:visible').length ? totalLapanganCell.find('.editable-input').val() : totalLapanganCell.find('.editable-text').text().trim();
                let keteranganCell = row.find("div[data-field='keterangan']");
                let keteranganVal = keteranganCell.find('.editable-input:visible').length ? keteranganCell.find('.editable-input').val() : keteranganCell.find('.editable-text').text().trim();
                
                totalLapanganVal = (totalLapanganVal === '' || totalLapanganVal === '-') ? '0' : totalLapanganVal.trim();
                keteranganVal = (keteranganVal === '-') ? '' : keteranganVal.trim();

                if (totalLapanganVal === '' || isNaN(parseInt(totalLapanganVal)) || parseInt(totalLapanganVal) < 0) {
                    Swal.fire('Error!', 'Stok Lapangan harus diisi dengan angka integer valid sebelum approve.', 'error'); return;
                }
                let parsedTotalLapangan = parseInt(totalLapanganVal);
                
                let stockSistemText = row.find("td:eq(5)").text().trim();
                let stockSistem = parseFloat(stockSistemText);
                if (isNaN(stockSistem)) { Swal.fire('Error!', 'Tidak bisa membaca Stok Sistem.', 'error'); return; }

                let selisihSementara = parsedTotalLapangan - stockSistem;
                if (selisihSementara != 0 && keteranganVal === '') {
                    if (parsedTotalLapangan != 0 || (parsedTotalLapangan == 0 && stockSistem == 0) ) {
                        Swal.fire('Peringatan!', 'Keterangan wajib diisi jika ada selisih sebelum approve.', 'warning');
                        if (keteranganCell.find('.editable-input:visible').length) { keteranganCell.find('.editable-input').focus(); } 
                        else { switchToEditMode(keteranganCell); keteranganCell.find('.editable-input').focus(); }
                        return;
                    }
                }

                Swal.fire({
                    title: 'Anda Yakin?', text: "Stok akan disesuaikan secara permanen!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Setujui!', cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        targetForm.find('input[name="total_lapangan"]').remove();
                        targetForm.find('input[name="keterangan"]').remove();
                        targetForm.append($('<input>', {type: 'hidden', name: 'total_lapangan', value: parsedTotalLapangan}));
                        targetForm.append($('<input>', {type: 'hidden', name: 'keterangan', value: keteranganVal}));
                        targetForm.submit();
                    }
                });
            });
        });
    </script>
@stop