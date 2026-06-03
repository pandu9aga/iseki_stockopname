@extends('layouts.main')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Missing Data</h4>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="card card-stats card-round" style="border-left: 5px solid #dc3545;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <div class="icon-big text-center">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                            </div>
                            <div class="col-9 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Missing Data</p>
                                    <h4 class="card-title">{{ $totalMissing }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card card-stats card-round" style="border-left: 5px solid #28a745;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <div class="icon-big text-center">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                            <div class="col-9 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Recorded</p>
                                    <h4 class="card-title">{{ $totalRecorded }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Base Data not found in Records</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="missing-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Location</th>
                                        <th>Code Rack</th>
                                        <th>Code Part</th>
                                        <th>Name Part</th>
                                        <th>Area</th>
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
        $('#missing-table').DataTable({
            pageLength: 50,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.missing.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'Location', name: 'Location' },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Area', name: 'Area' },
            ]
        });
    });
</script>
@endsection
