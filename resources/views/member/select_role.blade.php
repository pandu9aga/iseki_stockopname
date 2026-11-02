@extends('layouts.main')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header mb-4">
            <h3 class="fw-bold">Pilih Peran Stockopname</h3>
            <p class="text-muted">Halo, <strong>{{ $member->nama }}</strong> ({{ $member->nik }}). Pilih peran kerja yang akan Anda jalankan:</p>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <!-- Kurir -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'courier' ? 'primary' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-primary-subtle text-primary rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <h4 class="card-title fw-bold">Kurir</h4>
                        <p class="card-text text-muted small">Scan QR fisik rak, cetak QR label kurir via thermal bluetooth, dan upload foto part + rak kosong.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100">
                            @csrf
                            <input type="hidden" name="role" value="courier">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'courier' ? 'primary' : 'outline-primary' }} w-100">
                                {{ $session->active_role === 'courier' ? 'Peran Aktif Saat Ini' : 'Pilih Kurir' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Timbangan -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'scale' ? 'success' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-success-subtle text-success rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-weight"></i>
                        </div>
                        <h4 class="card-title fw-bold">Timbangan</h4>
                        <p class="card-text text-muted small">Dikerjakan berdua dengan Partner. Input nomor timbangan, hitung sample & validasi error margin (&lt; 2%).</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100">
                            @csrf
                            <input type="hidden" name="role" value="scale">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'scale' ? 'success' : 'outline-success' }} w-100">
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
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100">
                            @csrf
                            <input type="hidden" name="role" value="pos1">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'pos1' ? 'warning' : 'outline-warning' }} w-100">
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
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100">
                            @csrf
                            <input type="hidden" name="role" value="pos2">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'pos2' ? 'info' : 'outline-info' }} w-100">
                                {{ $session->active_role === 'pos2' ? 'Peran Aktif Saat Ini' : 'Pilih Pos 2' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pengembalian -->
            <div class="col">
                <div class="card h-100 shadow-sm border-{{ $session->active_role === 'returner' ? 'danger' : 'light' }}">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="p-3 bg-danger-subtle text-danger rounded-circle mb-3" style="font-size: 2rem;">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                        <h4 class="card-title fw-bold">Pengembalian</h4>
                        <p class="card-text text-muted small">Scan QR final, scan QR fisik rak tujuan, dan upload foto bukti part telah dikembalikan ke rak.</p>
                        <form action="{{ route('member.switch-role') }}" method="POST" class="mt-auto w-100">
                            @csrf
                            <input type="hidden" name="role" value="returner">
                            <button type="submit" class="btn btn-{{ $session->active_role === 'returner' ? 'danger' : 'outline-danger' }} w-100">
                                {{ $session->active_role === 'returner' ? 'Peran Aktif Saat Ini' : 'Pilih Pengembalian' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

