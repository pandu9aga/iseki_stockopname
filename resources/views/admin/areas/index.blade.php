@extends('layouts.main')

@section('style')
<style>
/* ── Admin Areas CRUD ── */
.area-table-wrapper { border-radius: 12px; overflow: hidden; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        {{-- Header --}}
        <div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Master Area</h3>
                <p class="text-muted mb-0 small">Kelola daftar nama area dan kode huruf area stock opname.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddArea" id="btnAddArea">
                <i class="fas fa-plus me-1"></i> Tambah Area
            </button>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- DataTable --}}
        <div class="card shadow-sm area-table-wrapper">
            <div class="card-body p-0">
                <table id="areasTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Nama Area (area_name)</th>
                            <th>Huruf / Kode Area (area_letter)</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- ── Modal Tambah ── --}}
<div class="modal fade" id="modalAddArea" tabindex="-1" aria-labelledby="modalAddAreaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAddAreaLabel"><i class="fas fa-plus-circle me-2"></i>Tambah Area</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.areas.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="add_area_name">Nama Area (area_name) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_area_name" name="area_name"
                               placeholder="Contoh: Area Machining, Gudang Utama" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="add_area_letter">Huruf / Kode Area (area_letter) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="add_area_letter" name="area_letter"
                               placeholder="Contoh: A, B, MCH" maxlength="255" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAdd">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Edit ── --}}
<div class="modal fade" id="modalEditArea" tabindex="-1" aria-labelledby="modalEditAreaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditAreaLabel"><i class="fas fa-edit me-2"></i>Edit Area</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditArea" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_area_name">Nama Area (area_name) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_area_name" name="area_name"
                               maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_area_letter">Huruf / Kode Area (area_letter) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="edit_area_letter" name="area_letter"
                               maxlength="255" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="fas fa-save me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function () {
    var table = $('#areasTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.areas.index") }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'area_name', name: 'area_name' },
            { data: 'area_letter', name: 'area_letter' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        },
        pageLength: 15,
        order: [[1, 'asc']],
    });

    // Open Edit Modal
    $(document).on('click', '.editArea', function () {
        var id     = $(this).data('id');
        var name   = $(this).data('name');
        var letter = $(this).data('letter');

        $('#edit_area_name').val(name);
        $('#edit_area_letter').val(letter);

        $('#formEditArea').attr('action', '/admin/areas/' + id);
        $('#modalEditArea').modal('show');
    });
});
</script>
@endsection

