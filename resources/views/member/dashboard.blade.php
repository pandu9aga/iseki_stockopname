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
                        <a href="{{ route('record.create') }}" class="btn btn-primary btn-round">Add New Record</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="records-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Code Part</th>
                                        <th>Name Part</th>
                                        <th>Rack</th>
                                        <th>Area</th>
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
    $(document).ready(function() {
        $('#records-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('dashboard') }}",
            columns: [
                { data: 'Time_Record', name: 'Time_Record' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Area', name: 'Area' },
                { data: 'Count_Record', name: 'Count_Record' },
            ],
            order: [[0, 'desc']]
        });
    });
</script>
@endsection
