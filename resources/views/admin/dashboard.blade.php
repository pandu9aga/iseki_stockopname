@extends('layouts.main')

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
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">All Stockopname Records</h4>
                            <form action="{{ route('admin.export') }}" method="GET" class="d-flex gap-2">
                                <input type="date" name="start_date" class="form-control form-control-sm" required>
                                <input type="date" name="end_date" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="admin-records-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Rack</th>
                                        <th>Count</th>
                                        <th>Member</th>
                                        <th>Time</th>
                                        <th>Name Part</th>
                                        <th>Code Part</th>
                                        <th>Seq</th>
                                        <th>Area</th>
                                        <th>Location</th>
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
        $('#admin-records-table').on('error.dt', function(e, settings, techNote, message) {
            console.log('An error has been reported by DataTables: ', message);
        }).DataTable({
            pageLength: 50,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.dashboard') }}",
            columns: [
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Count_Record', name: 'Count_Record' },
                { data: 'member_name', name: 'member_name', orderable: false },
                { data: 'Time_Record', name: 'Time_Record' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'No_Sequence', name: 'No_Sequence' },
                { data: 'Area', name: 'Area' },
                { data: 'Location', name: 'Location' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[3, 'desc']]
        });

        // Delete Record
        $(document).on('click', '.delete-record', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                $.ajax({
                    url: baseUrl + `admin/records/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                        $('#admin-records-table').DataTable().ajax.reload();
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
