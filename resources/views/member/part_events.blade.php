@extends('layouts.main')

@section('style')
<style>
    .events-header-card {
        background: linear-gradient(135deg, #1b2a47 0%, #283e68 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.5rem;
    }
    .event-card {
        border-radius: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid #e9ecef;
    }
    .event-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="events-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-boxes text-warning me-2"></i> Pilih Jadwal Stock Opname
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Pilih agenda SO di bawah untuk melihat snapshot seluruh part &amp; riwayat tahapan perhitungannya
                    </p>
                </div>
                <div>
                    <a href="{{ route('member.role.redirect') }}" class="btn btn-primary fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Ruang Kerja
                    </a>
                </div>
            </div>

            @if($activeEvent)
            <div class="alert alert-success bg-success bg-opacity-25 border-0 text-white mt-3 mb-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-check fa-lg text-success me-2"></i>
                    <span>Jadwal Aktif Berjalan: <strong>{{ $activeEvent->event_name }} ({{ $activeEvent->event_code }})</strong></span>
                </div>
                <a href="{{ route('member.parts.show-event', $activeEvent->id) }}" class="btn btn-sm btn-success fw-bold">
                    Buka Jadwal Aktif <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            @endif
        </div>

        <!-- Event Cards / Grid -->
        <div class="row g-3">
            @forelse($events as $ev)
                <div class="col-md-6 col-lg-4">
                    <div class="card event-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $ev->status === 'ACTIVE' ? 'bg-success' : ($ev->status === 'CLOSED' ? 'bg-secondary' : 'bg-warning text-dark') }} rounded-pill px-3 py-1">
                                    {{ $ev->status }}
                                </span>
                                <small class="text-muted font-monospace">{{ $ev->event_code }}</small>
                            </div>

                            <h5 class="fw-bold text-dark mb-1">{{ $ev->event_name }}</h5>
                            <p class="text-muted small mb-3">
                                <i class="far fa-calendar-alt me-1 text-primary"></i>
                                {{ $ev->start_date ? $ev->start_date->format('d M Y') : '-' }} s/d {{ $ev->end_date ? $ev->end_date->format('d M Y') : '-' }}
                            </p>

                            <div class="bg-light rounded p-2 mb-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total Part Snapshot:</span>
                                    <strong class="text-dark">{{ number_format($ev->parts_count) }} part</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Item Masuk SO:</span>
                                    <strong class="text-primary">{{ number_format($ev->so_items_count) }} item</strong>
                                </div>
                            </div>

                            @if($ev->description)
                                <p class="text-muted small text-truncate mb-3">{{ $ev->description }}</p>
                            @endif

                            <div class="mt-auto">
                                <a href="{{ route('member.parts.show-event', $ev->id) }}" class="btn {{ $ev->status === 'ACTIVE' ? 'btn-primary' : 'btn-outline-primary' }} w-100 fw-bold">
                                    <i class="fas fa-list-ul me-1"></i> Lihat Data Part SO <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card shadow-sm border-0 text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="fw-bold text-dark">Belum Ada Jadwal Stock Opname</h5>
                            <p class="text-muted small">Hubungi Admin untuk membuat jadwal SO baru dan menyalin snapshot part.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection

