@extends('adminlte::page')

@section('title', 'History')

@section('content_header')
    <h1>History Aktivitas</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Log Aktivitas Sistem</h3>
        </div>
        <div class="card-body">
            <table id="history-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px;">No</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Tabel</th>
                        <th>Data Lama</th>
                        <th>Data Baru</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($histories as $key => $history)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $history->user->name ?? 'Sistem' }}</td>
                            <td>
                                @if($history->action == 'created')
                                    <span class="badge badge-success">Create</span>
                                @elseif($history->action == 'updated')
                                    <span class="badge badge-primary">Update</span>
                                @elseif($history->action == 'deleted')
                                    <span class="badge badge-danger">Delete</span>
                                @else
                                    <span class="badge badge-secondary">{{ $history->action }}</span>
                                @endif
                            </td>
                            <td>{{ $history->table_name }} (ID: {{ $history->record_id }})</td>
                            <td>
                                @if (!empty($history->old_values))
                                    @foreach ($history->old_values as $field => $value)
                                        <p class="mb-1">
                                            {{-- Mengubah snake_case menjadi format Judul --}}
                                            <strong>{{ Str::title(str_replace('_', ' ', $field)) }}:</strong>
                                            {{ $value }}
                                        </p>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                             <td>
                                @if (!empty($history->new_values))
                                    @foreach ($history->new_values as $field => $value)
                                        <p class="mb-1">
                                            <strong>{{ Str::title(str_replace('_', ' ', $field)) }}:</strong>
                                            {{ $value }}
                                        </p>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $history->created_at->format('d M Y, H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#history-table').DataTable({
                "order": [[ 6, "desc" ]],
                responsive: true,
            });
        });
    </script>
@stop