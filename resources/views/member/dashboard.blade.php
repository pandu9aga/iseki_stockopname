@extends('layouts.main')

@section('style')
<style>
    #records-table { font-size: 13px; }
    #records-table td, #records-table th { padding: 4px 6px !important; vertical-align: middle; }
    #records-table tbody tr.table-danger { background-color: #f8d7da !important; }
    .sub-row { display: block; font-size: 11px; }
    .sub-row + .sub-row { border-top: 1px dashed #ddd; padding-top: 2px; margin-top: 2px; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Member Dashboard</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Stockopname Records</h4>
                        <a href="{{ route('record.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-qrcode"></i> Record</a>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-secondary filter-btn active" data-filter="all">All</button>
                                <button type="button" class="btn btn-success filter-btn" data-filter="2">OK (2x)</button>
                                <button type="button" class="btn btn-warning filter-btn" data-filter="1">NG 1x</button>
                                <button type="button" class="btn btn-danger filter-btn" data-filter="0">NG 0x</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="records-table" class="display table table-sm table-striped table-hover">
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

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var rowData = tableData[dataIndex];
        if (scanFilter === 'all') return true;
        return rowData.scan_count == scanFilter;
    });

    $(document).ready(function() {
        var table = $('#records-table').DataTable({
            pageLength: 50,
            data: tableData,
            columns: [
                { data: null, render: function(data, type, row, meta) { return meta.row + 1; } },
                { data: 'Code_Rack' },
                { data: 'Location' },
                { data: 'Code_Part' },
                { data: 'Name_Part' },
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
            ],
            createdRow: function(row, data) {
                if (data.scan_count < 2) {
                    $(row).addClass('table-danger');
                }
            }
        });

        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            scanFilter = $(this).data('filter');
            table.draw();
        });
    });
</script>
@endsection
