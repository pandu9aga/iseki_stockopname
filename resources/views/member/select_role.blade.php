@extends('layouts.main')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header mb-4">
            <h3 class="fw-bold">Pilih Peran & Area Kerja Stock Opname</h3>
            <p class="text-muted">Halo, <strong>{{ $member->nama }}</strong> ({{ $member->nik }}). Tentukan area kerja dan pilih peran yang akan Anda jalankan:</p>
        </div>

        @php
            $activeEvent = \App\Models\SoEvent::getActiveEvent();
        @endphp

        @if(!$activeEvent)
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4" role="alert" style="border-radius: 10px;">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                <div>
                    <h6 class="fw-bold mb-1">Perhatian: Belum Ada Jadwal Stock Opname yang Aktif</h6>
                    <p class="mb-0 small">
                        Saat ini sistem belum memiliki jadwal SO berstatus <strong>ACTIVE</strong>. Anda tetap dapat memilih peran, namun modul Kurir tidak dapat memulai proses scan rak sampai Admin mengaktifkan jadwal SO.
                    </p>
                </div>
            </div>
        @else
            <div class="alert alert-success shadow-sm border-0 d-flex align-items-center justify-content-between mb-4 py-2 px-3" style="border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-check me-2 text-success"></i>
                    <span class="small">Jadwal SO Berjalan: <strong>{{ $activeEvent->event_name }}</strong> ({{ $activeEvent->event_code }})</span>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-1" style="font-size: 0.72rem;">STATUS AKTIF</span>
            </div>
        @endif

        {{-- Section Pemilihan Area Kerja --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="card-body p-3 p-md-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <div class="d-flex align-items-center">
                            <div class="p-3 bg-primary text-white rounded-circle me-3 shadow-sm">
                                <i class="fas fa-map-marked-alt fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">Area Kerja Stock Opname</h5>
                                <p class="text-muted small mb-0">Pilih area tempat Anda bertugas saat ini. Kurir hanya akan memproses part sesuai abjad area yang dipilih.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label for="sharedAreaSelect" class="form-label fw-bold text-dark small mb-1">
                            Pilih Area Anda: <span class="text-danger">*</span>
                        </label>
                        <select id="sharedAreaSelect" class="form-select form-select-lg border-primary fw-bold shadow-sm" required>
                            @if($areas->isEmpty())
                                <option value="" disabled selected>— Belum ada area di master area (Hubungi Admin) —</option>
                            @else
                                <option value="" disabled {{ !$session->area_id ? 'selected' : '' }}>-- Pilih Area Kerja --</option>
                                @foreach($areas as $ar)
                                    <option value="{{ $ar->id }}" data-letter="{{ $ar->area_letter }}" {{ $session->area_id == $ar->id ? 'selected' : '' }}>
                                        [{{ strtoupper($ar->area_letter) }}] {{ $ar->area_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div id="areaFeedback" class="small text-muted mt-1">
                            @if($session->area)
                                <i class="fas fa-check-circle text-success me-1"></i> Area aktif Anda: <strong>[{{ strtoupper($session->area_letter ?? $session->area->area_letter) }}] {{ $session->area->area_name }}</strong>
                            @else
                                <i class="fas fa-info-circle text-danger me-1"></i> <strong>Wajib pilih area</strong> terlebih dahulu sebelum memilih peran di bawah.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
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

        <div class="row row-cols-1 row-cols-md-3 g-4">
            @if($isMerged)
            <!-- Peran Kurir & Pengembalian Terpadu (Digabung) -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ in_array($session->active_role, ['courier', 'returner']) ? 'primary' : 'light' }}" style="{{ in_array($session->active_role, ['courier', 'returner']) ? 'border-color: #ff3483 !important;' : '' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 rounded-circle mb-3 text-white" style="font-size: 2rem; background: linear-gradient(135deg, #ff3483 0%, #e06d80 100%);">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4 class="card-title fw-bold">Kurir &amp; Pengembalian</h4>
                        <p class="card-text text-muted small">
                            Peran terpadu: Scan QR rak fisik untuk ambil &amp; cetak label kurir, atau scan QR label final untuk kembalikan part ke rak.
                        </p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="courier">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ in_array($session->active_role, ['courier', 'returner']) ? 'primary' : 'outline-primary' }} w-100 btn-select-role">
                                {{ in_array($session->active_role, ['courier', 'returner']) ? 'Peran Aktif Saat Ini' : 'Pilih Kurir & Pengembalian' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @else
            <!-- Kurir Terpisah -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'courier' ? 'primary' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-primary-subtle text-primary rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <h4 class="card-title fw-bold">Kurir</h4>
                        <p class="card-text text-muted small">Scan QR fisik rak, cetak QR label kurir via thermal bluetooth, dan upload foto part + rak kosong.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="courier">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'courier' ? 'primary' : 'outline-primary' }} w-100 btn-select-role">
                                {{ $session->active_role === 'courier' ? 'Peran Aktif Saat Ini' : 'Pilih Kurir' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Timbangan -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'scale' ? 'success' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-success-subtle text-success rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-weight"></i>
                        </div>
                        <h4 class="card-title fw-bold">Timbangan</h4>
                        <p class="card-text text-muted small">Dikerjakan berdua dengan Partner. Input nomor timbangan, hitung sample & validasi error margin (&lt; 2%).</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="scale">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'scale' ? 'success' : 'outline-success' }} w-100 btn-select-role">
                                {{ $session->active_role === 'scale' ? 'Peran Aktif Saat Ini' : 'Pilih Timbangan' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pos 1 Manual -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'pos1' ? 'warning' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-warning-subtle text-warning rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4 class="card-title fw-bold">Pos 1 (Manual)</h4>
                        <p class="card-text text-muted small">Perhitungan manual tahap 1. Scan QR kurir, dobel input jumlah hitungan, dan upload foto part.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="pos1">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'pos1' ? 'warning' : 'outline-warning' }} w-100 btn-select-role">
                                {{ $session->active_role === 'pos1' ? 'Peran Aktif Saat Ini' : 'Pilih Pos 1' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pos 2 Manual -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'pos2' ? 'info' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-info-subtle text-info rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h4 class="card-title fw-bold">Pos 2 (Manual)</h4>
                        <p class="card-text text-muted small">Verifikasi hasil Pos 1. Jika hitungan sama (OK), cetak label QR final. Jika beda, status tercatat NG.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="pos2">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'pos2' ? 'info' : 'outline-info' }} w-100 btn-select-role">
                                {{ $session->active_role === 'pos2' ? 'Peran Aktif Saat Ini' : 'Pilih Pos 2' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if(!$isMerged)
            <!-- Pengembalian Terpisah -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'returner' ? 'danger' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-danger-subtle text-danger rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                        <h4 class="card-title fw-bold">Pengembalian</h4>
                        <p class="card-text text-muted small">Scan QR final, scan QR fisik rak tujuan, dan upload foto bukti part telah dikembalikan ke rak.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100 role-submit-form">
                            @csrf
                            <input type="hidden" name="role" value="returner">
                            <input type="hidden" name="area_id" class="inputRoleAreaId" value="{{ $session->area_id }}">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'returner' ? 'danger' : 'outline-danger' }} w-100 btn-select-role">
                                {{ $session->active_role === 'returner' ? 'Peran Aktif Saat Ini' : 'Pilih Pengembalian' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const areaSelect = document.getElementById('sharedAreaSelect');
    const areaFeedback = document.getElementById('areaFeedback');
    const roleForms = document.querySelectorAll('.role-submit-form');
    const hiddenAreaInputs = document.querySelectorAll('.inputRoleAreaId');

    function syncAreaToForms() {
        const val = areaSelect.value;
        const selectedOpt = areaSelect.options[areaSelect.selectedIndex];
        
        hiddenAreaInputs.forEach(input => {
            input.value = val;
        });

        if (val) {
            const letter = selectedOpt.getAttribute('data-letter') || '';
            areaFeedback.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> Area aktif yang dipilih: <strong>${selectedOpt.text}</strong>`;
        } else {
            areaFeedback.innerHTML = `<i class="fas fa-exclamation-triangle text-danger me-1"></i> <strong>Pilih area kerja terlebih dahulu!</strong>`;
        }
    }

    areaSelect.addEventListener('change', syncAreaToForms);

    roleForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!areaSelect.value) {
                e.preventDefault();
                alert('Silakan pilih Area Kerja terlebih dahulu pada pilihan di atas sebelum memilih peran!');
                areaSelect.focus();
            }
        });
    });
});
</script>
@endsection