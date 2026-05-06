@extends('layouts.main')

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
                        <div class="table-responsive">
                            <table id="records-table" class="display table table-striped table-hover">
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
        $('#records-table').on('error.dt', function(e, settings, techNote, message) {
            console.log('An error has been reported by DataTables: ', message);
        }).DataTable({
            pageLength: 50,
            processing: true,
            serverSide: true,
            ajax: "{{ route('dashboard') }}",
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
                { data: 'photos', name: 'photos', orderable: false, searchable: false },
            ],
            order: [[3, 'desc']]
        });
    });
</script>
@endsection
