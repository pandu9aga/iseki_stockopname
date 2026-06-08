@extends('layouts.main')

@section('style')
<style>
    #admin-records-table { font-size: 13px; }
    #admin-records-table td, #admin-records-table th { padding: 4px 6px !important; vertical-align: middle; }
    #admin-records-table tbody tr.table-danger { background-color: #f8d7da !important; }
    #admin-records-table tbody tr.table-success { background-color: #d4edda !important; }
    .sub-row { display: block; font-size: 11px; }
    .sub-row + .sub-row { border-top: 1px dashed #ddd; padding-top: 2px; margin-top: 2px; }
    .name-part-cell { font-size: 10px !important; max-width: 200px; }
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
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Stockopname Records</h4>
                            <form action="{{ route('admin.export') }}" method="GET" class="d-flex gap-2">
                                <input type="date" name="start_date" class="form-control form-control-sm" required>
                                <input type="date" name="end_date" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-secondary filter-btn active" data-filter="all">All</button>
                                <button type="button" class="btn btn-success filter-btn" data-filter="ok">OK (2x)</button>
                                <button type="button" class="btn filter-btn" data-filter="ng_count" style="background-color:#6f42c1;border-color:#6f42c1;color:#fff">Count NG (2x)</button>
                                <button type="button" class="btn btn-warning filter-btn" data-filter="1">NG 1x</button>
                                <button type="button" class="btn btn-danger filter-btn" data-filter="0">NG 0x</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="admin-records-table" class="display table table-sm table-striped table-hover">
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
                                        <th>Dual</th>
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
    var scanFilter = 'all';

    function getRowClass(data) {
        if (data.scan_count >= 2 && data.Count_A === data.Count_B) return 'table-success';
        return 'table-danger';
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var rowData = tableData[dataIndex];
        if (scanFilter === 'all') return true;
        if (scanFilter === 'ok') return rowData.scan_count >= 2 && rowData.Count_A === rowData.Count_B;
        if (scanFilter === 'ng_count') return rowData.scan_count >= 2 && rowData.Count_A !== rowData.Count_B;
        return rowData.scan_count == scanFilter;
    });

    $(document).ready(function() {
        var table = $('#admin-records-table').DataTable({
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
                        var html = '<span class="sub-row">' + (data.Time_A || '-') + '</span>';
                        html += '<span class="sub-row">' + (data.Time_B || '-') + '</span>';
                        return html;
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        var html = '<span class="sub-row">' + (data.Member_A || '-') + '</span>';
                        html += '<span class="sub-row">' + (data.Member_B || '-') + '</span>';
                        return html;
                    }
                },
                {
                    data: null,
                    render: function(data) {
                        var html = '<span class="sub-row">' + (data.Count_A || '-') + '</span>';
                        html += '<span class="sub-row">' + (data.Count_B || '-') + '</span>';
                        return html;
                    }
                },
                { data: 'Dual_Count', render: function(v) { return v || 0; } },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function() {
                        return '<button class="btn btn-secondary btn-sm view-group"><i class="fas fa-eye"></i></button>';
                    }
                },
                { data: 'action', orderable: false, searchable: false },
            ],
            createdRow: function(row, data) {
                $(row).addClass(getRowClass(data));
            }
        });

        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            scanFilter = $(this).data('filter');
            table.draw();
        });

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
