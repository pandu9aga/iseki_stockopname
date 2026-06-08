@extends('layouts.main')

@section('style')
<style>
    #dual-check-table { font-size: 13px; }
    #dual-check-table td, #dual-check-table th { padding: 4px 6px !important; vertical-align: middle; }
    .sub-row { display: block; font-size: 11px; }
    .sub-row + .sub-row { border-top: 1px dashed #ddd; padding-top: 2px; margin-top: 2px; }
    .name-part-cell { font-size: 10px !important; max-width: 200px; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Dual Check Dashboard</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Dual Check Records</h4>
                        <a href="{{ route('dual-check.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-qrcode"></i> New Dual Check</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dual-check-table" class="display table table-sm table-striped table-hover">
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
        var table = $('#dual-check-table').DataTable({
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
                    render: function(data) {
                        return '<button class="btn btn-secondary btn-sm view-dual-group"><i class="fas fa-eye"></i></button>';
                    }
                },
            ]
        });

        $(document).on('click', '.view-dual-group', function(e) {
            e.stopPropagation();
            var t = $(this).closest('table').DataTable();
            var d = t.row($(this).closest('tr')).data();
            showDualGroupDetail(d);
        });

        $(document).on('click', '#dual-check-table tbody tr', function(e) {
            if ($(e.target).closest('button, a, input, select, .no-click').length) return;
            var t = $('#dual-check-table').DataTable();
            var d = t.row(this).data();
            showDualGroupDetail(d);
        });
    });
</script>
@endsection
