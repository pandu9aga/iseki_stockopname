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
                                        <th>Time</th>
                                        <th>Code Part</th>
                                        <th>Name Part</th>
                                        <th>Rack</th>
                                        <th>Seq</th>
                                        <th>Area</th>
                                        <th>NIK</th>
                                        <th>Count</th>
                                        <th>Photos</th>
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
                { data: 'Time_Record', name: 'Time_Record' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'No_Sequence', name: 'No_Sequence' },
                { data: 'Area', name: 'Area' },
                { data: 'NIK', name: 'NIK' },
                { data: 'Count_Record', name: 'Count_Record' },
                { data: 'photos', name: 'photos', orderable: false, searchable: false },
            ],
            order: [[0, 'desc']]
        });
    });
</script>
@endsection
