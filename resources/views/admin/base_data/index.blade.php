@extends('layouts.main')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Base Data</h4>
        </div>
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Base Data List</h4>
                        @if(Auth::guard('admin')->user()->name === 'saiful')
                        <div>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-file-excel"></i> Import Excel</button>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBaseDataModal"><i class="fas fa-plus"></i> Add Data</button>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="base-data-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Location</th>
                                        <th>Code Rack</th>
                                        <th>Code Part</th>
                                        <th>Name Part</th>
                                        <th>Area</th>
                                        @if(Auth::guard('admin')->user()->name === 'saiful')<th>Action</th>@endif
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

<!-- Add Modal -->
<div class="modal fade" id="addBaseDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Add Base Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.base-data.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Code Part</label>
                        <input type="text" name="Code_Part" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Name Part</label>
                        <input type="text" name="Name_Part" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code Rack</label>
                        <input type="text" name="Code_Rack" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="Area" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="Location" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editBaseDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Edit Base Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBaseDataForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Code Part</label>
                        <input type="text" name="Code_Part" id="edit_Code_Part" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Name Part</label>
                        <input type="text" name="Name_Part" id="edit_Name_Part" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Code Rack</label>
                        <input type="text" name="Code_Rack" id="edit_Code_Rack" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="Area" id="edit_Area" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="Location" id="edit_Location" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Import Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.base-data.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File (.xlsx / .xls)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="alert alert-info">
                        <strong>Format:</strong> Column B = Location, C = Code_Rack, D = Code_Part, E = Name_Part, F = Area.<br>
                        Row 1 (header) is skipped automatically.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        var table = $('#base-data-table').DataTable({
            pageLength: 50,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.base-data.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'Location', name: 'Location' },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Code_Part', name: 'Code_Part' },
                { data: 'Name_Part', name: 'Name_Part' },
                { data: 'Area', name: 'Area' },
                @if(Auth::guard('admin')->user()->name === 'saiful')
                { data: 'action', name: 'action', orderable: false, searchable: false },
                @endif
            ]
        });

        $(document).on('click', '.editBaseData', function() {
            $('#edit_Code_Part').val($(this).data('code_part'));
            $('#edit_Name_Part').val($(this).data('name_part'));
            $('#edit_Code_Rack').val($(this).data('code_rack'));
            $('#edit_Area').val($(this).data('area'));
            $('#edit_Location').val($(this).data('location'));
            $('#editBaseDataForm').attr('action', "{{ url('admin/base-data') }}/" + $(this).data('id'));
            $('#editBaseDataModal').modal('show');
        });
    });
</script>
@endsection
