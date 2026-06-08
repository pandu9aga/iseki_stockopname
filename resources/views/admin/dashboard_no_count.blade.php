@extends('layouts.main')

@section('style')
<style>
    #records-table { font-size: 13px; }
    #records-table td, #records-table th { padding: 4px 6px !important; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Admin Dashboard</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                            <h4 class="card-title mb-0">Stockopname Records</h4>
                            <form action="{{ route('admin.export') }}" method="GET" class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center">
                                <select name="area" class="form-control form-control-sm" required>
                                    <option value="">Select Area</option>
                                    @foreach ($areas as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="start_date" class="form-control form-control-sm" required>
                                <input type="date" name="end_date" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="records-table" class="display table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Rack</th>
                                        <th>Time</th>
                                        <th>Name Part</th>
                                        <th>Code Part</th>
                                        <th>Area</th>
                                        <th>Location</th>
                                        <th>Photos</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
        $('#records-table').on('error.dt', function(e, settings, techNote, message) {
            console.log('An error has been reported by DataTables: ', message);
        }).DataTable({
            pageLength: 50,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.dashboard.nocount') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Time_Record', name: 'Time_Record' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'Area', name: 'Area' },
                { data: 'Location', name: 'Location' },
                { data: 'photos', name: 'photos', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[2, 'desc']]
        });

        // Delete Record
        $(document).on('click', '.delete-record', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'admin/records/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                        $('#records-table').DataTable().ajax.reload();
                    },
                    error: function(err) {
                        alert('Error: ' + (err.responseJSON ? err.responseJSON.message : 'Failed to delete record'));
                    }
                });
            }
        });
    });
</script>
@endsection
