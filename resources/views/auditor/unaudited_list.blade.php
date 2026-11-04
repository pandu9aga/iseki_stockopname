@extends('layouts.main')

@section('style')
<style>
    .letter-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
        color: #495057;
        background: #fff;
    }
    .letter-pill:hover {
        background: #e9ecef;
        color: #000;
    }
    .letter-pill.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.35);
    }
    .unaudited-card {
        border-radius: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .unaudited-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.08);
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header Navigation -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fas fa-boxes text-warning me-2"></i> Daftar Part Selesai Dikembalikan (Unaudited List)
                </h3>
                <p class="text-muted small mb-0">
                    Daftar part yang sudah dikembalikan ke rak fisik oleh tim Return (<code>RETURNED</code>) dan siap dilakukan audit fisik oleh Auditor.
                </p>
            </div>
            <div>
                <a href="{{ route('auditor.portal') }}" class="btn btn-primary fw-bold shadow-sm">
                    <i class="fas fa-qrcode me-1"></i> Scan Label Pengembalian
                </a>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-primary border-4" style="border-radius:12px;">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Total Part Dikembalikan</small>
                            <span class="fs-4 fw-bold text-dark">{{ number_format($totalAllMaster) }}</span>
                        </div>
                        <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                            <i class="fas fa-undo-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-success border-4" style="border-radius:12px;">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Sudah Diaudit</small>
                            <span class="fs-4 fw-bold text-success">{{ number_format($totalAuditedMaster) }}</span>
                        </div>
                        <div class="p-3 bg-success-subtle text-success rounded-circle">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 border-start border-warning border-4" style="border-radius:12px;">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Menunggu Audit</small>
                            <span class="fs-4 fw-bold text-danger">{{ number_format($totalUnaudited) }}</span>
                        </div>
                        <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alphabetical Quick Filter (A - Z) -->
        <div class="card shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-muted text-uppercase">
                        <i class="fas fa-filter me-1"></i> Filter Berdasarkan Awalan Rak:
                    </span>
                    <span class="badge bg-light text-dark">{{ count($unauditedItems) }} part ditampilkan</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('auditor.unaudited', ['letter' => 'ALL']) }}"
                       class="btn btn-sm {{ $activeLetter === 'ALL' ? 'btn-dark' : 'btn-outline-secondary' }} fw-bold px-3">
                        SEMUA
                    </a>
                    @foreach($availableLetters as $char)
                    <a href="{{ route('auditor.unaudited', ['letter' => $char]) }}"
                       class="letter-pill {{ $activeLetter === $char ? 'active' : '' }}">
                        {{ $char }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Unaudited Items Grid -->
        <div class="row g-3">
            @forelse($unauditedItems as $item)
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card shadow-sm h-100 unaudited-card border-0">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info small">
                                    <i class="fas fa-undo-alt me-1"></i> Returned
                                </span>
                                <span class="badge bg-light text-muted font-monospace small">#{{ $item['letter'] }}</span>
                            </div>
                            <h5 class="fw-bold font-monospace text-primary mb-1">{{ $item['rack_no'] }}</h5>
                            <div class="text-dark fw-semibold small mb-1">{{ $item['code_part'] }}</div>
                            <div class="text-muted small text-truncate" title="{{ $item['name_part'] }}">{{ $item['name_part'] }}</div>
                            <div class="mt-2 p-2 bg-light rounded small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Hasil Hitung:</span>
                                    <strong class="text-success">{{ number_format($item['final_count']) }} pcs</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Dikembalikan:</span>
                                    <span class="font-monospace text-muted">{{ $item['returned_at'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 mt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="text-muted small">LOC: <strong>{{ $item['loc'] }} - {{ $item['cell'] }}</strong></span>
                            <a href="{{ route('auditor.portal') }}?rack={{ urlencode($item['tracking_code']) }}" class="btn btn-sm btn-primary" title="Audit part ini sekarang">
                                <i class="fas fa-edit me-1"></i> Audit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card shadow-sm p-5 text-center" style="border-radius:14px;">
                    <div class="p-4 bg-light rounded-circle d-inline-flex mb-3 mx-auto">
                        <i class="fas fa-check-double fa-3x text-success"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Tidak Ada Part Menunggu Audit</h5>
                    <p class="text-muted small mb-0">
                        Belum ada part yang selesai dikembalikan oleh tim Return, atau semua part berawalan <strong>"{{ $activeLetter }}"</strong> sudah diaudit.
                    </p>
                </div>
            </div>
            @endforelse
        </div>

    </div>
</div>
@endsection

