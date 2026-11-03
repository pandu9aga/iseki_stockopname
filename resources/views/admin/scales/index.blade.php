@extends('layouts.main')

@section('style')
<style>
/* ── Admin Scales CRUD ── */
.scale-table-wrapper { border-radius: 12px; overflow: hidden; }
.badge-active   { background: linear-gradient(135deg, #28a745, #20c997); }
.badge-inactive { background: linear-gradient(135deg, #6c757d, #adb5bd); }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        {{-- Header --}}
        <div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-0"><i class="fas fa-balance-scale me-2 text-primary"></i>Master Timbangan</h3>
                <p class="text-muted mb-0 small">Kelola daftar nomor timbangan yang digunakan saat stockopname.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddScale" id="btnAddScale">
                <i class="fas fa-plus me-1"></i> Tambah Timbangan
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
        <div class="card shadow-sm scale-table-wrapper">
            <div class="card-body p-0">
                <table id="scalesTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nomor Timbangan</th>
                            <th>Nama / Merek</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- ── Modal Tambah ── --}}
<div class="modal fade" id="modalAddScale" tabindex="-1" aria-labelledby="modalAddScaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAddScaleLabel"><i class="fas fa-plus-circle me-2"></i>Tambah Timbangan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.scales.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="add_scale_number">Nomor / Kode Timbangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_scale_number" name="scale_number"
                               placeholder="Contoh: TB-01, SCALE-A" maxlength="50" required>
                        <div class="form-text">Harus unik. Format bebas (huruf, angka, tanda hubung).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="add_scale_name">Nama / Merek</label>
                        <input type="text" class="form-control" id="add_scale_name" name="scale_name"
                               placeholder="Contoh: Ohaus Ranger, A&D FX-3000" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="add_location">Lokasi</label>
                        <input type="text" class="form-control" id="add_location" name="location"
                               placeholder="Contoh: Gudang A, Line 2" maxlength="100">
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
<div class="modal fade" id="modalEditScale" tabindex="-1" aria-labelledby="modalEditScaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditScaleLabel"><i class="fas fa-edit me-2"></i>Edit Timbangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditScale" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_scale_number">Nomor / Kode Timbangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_scale_number" name="scale_number"
                               maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_scale_name">Nama / Merek</label>
                        <input type="text" class="form-control" id="edit_scale_name" name="scale_name" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="edit_location">Lokasi</label>
                        <input type="text" class="form-control" id="edit_location" name="location" maxlength="100">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Aktif</label>
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
{{-- DataTables --}}
<script>
$(document).ready(function () {

    var table = $('#scalesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.scales.index") }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'scale_number', name: 'scale_number' },
            { data: 'scale_name',   name: 'scale_name', defaultContent: '<span class="text-muted">-</span>' },
            { data: 'location',     name: 'location',   defaultContent: '<span class="text-muted">-</span>' },
            { data: 'status_badge', name: 'is_active', orderable: false, searchable: false },
            { data: 'action',       name: 'action',     orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        },
        pageLength: 15,
        order: [[1, 'asc']],
    });

    // Open Edit Modal
    $(document).on('click', '.editScale', function () {
        var id       = $(this).data('id');
        var number   = $(this).data('number');
        var name     = $(this).data('name');
        var location = $(this).data('location');
        var active   = $(this).data('active');

        $('#edit_scale_number').val(number);
        $('#edit_scale_name').val(name);
        $('#edit_location').val(location);
        $('#edit_is_active').prop('checked', parseInt(active) === 1);

        $('#formEditScale').attr('action', '/admin/scales/' + id);
        $('#modalEditScale').modal('show');
    });

});
</script>
@endsection
