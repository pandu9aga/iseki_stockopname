@extends('layouts.main')

@section('style')
<style>
    #admin-dual-table { font-size: 13px; }
    #admin-dual-table td, #admin-dual-table th { padding: 4px 6px !important; vertical-align: middle; }
    .sub-row { display: block; font-size: 11px; }
    .sub-row + .sub-row { border-top: 1px dashed #ddd; padding-top: 2px; margin-top: 2px; }
    .name-part-cell { font-size: 10px !important; max-width: 200px; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Admin Dual Check</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Dual Check Records (Grouped)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="admin-dual-table" class="display table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Rack</th>
                                        <th>Location</th>
                                        <th>Code Part</th>
                                        <th>Name Part</th>
                                        <th>Area</th>
                                        <th>Time</th>
                                        <th>Member</th>
                                        <th>Count</th>
                                        <th>View</th>
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
    var tableData = @json($rows);

    $(document).ready(function() {
        var table = $('#admin-dual-table').DataTable({
            pageLength: 50,
            data: tableData,
            columns: [
                { data: null, render: function(data, type, row, meta) { return meta.row + 1; } },
                { data: 'Code_Rack' },
                { data: 'Location' },
                { data: 'Code_Part' },
                { data: 'Name_Part', render: function(v) { var txt = v && v.length > 50 ? v.substring(0, 50) + '...' : v; return '<span style=\"font-size:10px\">' + (txt || '') + '</span>'; } },
                { data: 'Area' },
                {
                    data: null,
                    render: function(data) {
                        if (!data.times || data.times.length === 0) return '-';
                        var html = '';
                        data.times.forEach(function(t) {
                            html += '<span class="sub-row">' + (t || '-') + '</span>';
                        });
                        return html;
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        if (!data.members || data.members.length === 0) return '-';
                        var html = '';
                        data.members.forEach(function(m) {
                            html += '<span class="sub-row">' + (m || '-') + '</span>';
                        });
                        return html;
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        if (!data.counts || data.counts.length === 0) return '-';
                        var html = '';
                        data.counts.forEach(function(c) {
                            html += '<span class="sub-row">' + (c || '-') + '</span>';
                        });
                        return html;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function() {
                        return '<button class="btn btn-secondary btn-sm view-dual-group"><i class="fas fa-eye"></i></button>';
                    }
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        if (!data || data.length === 0) return '';
                        var html = '';
                        data.forEach(function(a) {
                            html += '<span class="sub-row">' + (a || '') + '</span>';
                        });
                        return html;
                    }
                },
            ]
        });

        $(document).on('click', '.delete-record', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'admin/records/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        alert(response.message);
                        location.reload();
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
