@extends('layouts.main')

@section('style')
<link rel="stylesheet" href="{{ asset('assets/js/plugin/datatables/datatables.min.css') }}" />
<style>
    .timer-page-header {
        background: linear-gradient(135deg, #0d3b66 0%, #1f4e78 100%);
        color: #fff;
        border-radius: 12px;
        padding: 1.25rem;
    }
    .kpi-card {
        border-radius: 10px;
        border: 1px solid rgba(0,0,0,0.06);
        background: #fff;
        padding: 0.65rem 0.85rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .kpi-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    /* Compact Table Styles */
    #timerDataTable {
        font-size: 0.72rem !important;
        table-layout: fixed;
        width: 100% !important;
    }
    #timerDataTable thead th {
        font-size: 0.7rem !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 700;
        vertical-align: middle;
        background-color: #f1f4f9 !important;
        border-bottom: 2px solid #dee2e6;
        padding: 6px 4px !important;
        text-align: center;
    }
    #timerDataTable tbody td {
        font-size: 0.72rem !important;
        vertical-align: middle;
        padding: 5px 4px !important;
        line-height: 1.2;
    }
    .timer-badge {
        font-size: 0.68rem !important;
        padding: 2px 5px !important;
        letter-spacing: -0.2px;
    }
    .timer-subtext {
        font-size: 0.62rem !important;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table-responsive {
        overflow-x: hidden !important; /* Hilangkan scroll samping */
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="timer-page-header shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $event->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }} px-3 py-1">
                            {{ $event->status }}
                        </span>
                        <span class="text-white-50 small font-monospace">{{ $event->event_code }}</span>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-stopwatch text-warning me-2"></i> Timer & Lead Time: {{ $event->event_name }}
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Perhitungan durasi antar scan tugas berurutan oleh user/petugas yang sama (Delta Time)
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.timers.index') }}" class="btn btn-outline-light fw-bold shadow-sm">
                        <i class="fas fa-list me-1"></i> Pilih Event Lain
                    </a>
                    <a href="{{ route('admin.so-events.parts', $event->id) }}" class="btn btn-warning text-dark fw-bold shadow-sm">
                        <i class="fas fa-boxes me-1"></i> Buka List Part SO
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI Cards Summary (Kurir, Pos 1, Pos 2, Timbangan, Return, Siklus) -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-info-subtle text-info">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Rata-rata Kurir</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_courier_formatted'] }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-primary-subtle text-primary">
                        <i class="fas fa-hand-paper"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Rata-rata Pos 1</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_pos1_formatted'] }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-secondary-subtle text-secondary">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Rata-rata Pos 2</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_pos2_formatted'] }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-warning-subtle text-warning">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Rata-rata Timbang</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_scale_formatted'] }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-success-subtle text-success">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Rata-rata Return</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_return_formatted'] }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="kpi-card shadow-sm d-flex align-items-center gap-2">
                    <div class="kpi-icon bg-dark text-white">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.68rem;">Siklus Part Rak</div>
                        <h6 class="fw-bold text-dark mb-0 font-monospace">{{ $summaryStats['avg_cycle_formatted'] }}</h6>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($areaCards) && count($areaCards) > 0)
        <!-- Area Grid Cards -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;">
                    <i class="fas fa-th-large text-primary me-2"></i>Pilih Area Kerja (Klik Card untuk Memfilter List Timer)
                </h6>
                <button type="button" class="btn btn-xs btn-outline-secondary btn-reset-area d-none" id="btnResetAreaFilter">
                    <i class="fas fa-times me-1"></i> Tampilkan Semua Area
                </button>
            </div>
            <div class="row g-1 mb-2" id="areaCardsContainer">
                @foreach($areaCards as $card)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl" style="min-width: 140px;">
                    <div class="card shadow-sm border h-100 area-card-box p-2 bg-white {{ !empty($card['is_extra']) ? 'border-warning-subtle' : '' }}" data-area-no="{{ $card['area_no'] }}" style="cursor: pointer; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="text-truncate">
                                @if(!empty($card['is_extra']))
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 fw-bold" style="font-size:0.7rem;">
                                    <i class="fas fa-plus-circle text-warning me-1"></i>Part Tambahan
                                </span>
                                @else
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold" style="font-size:0.72rem;">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $card['card_title'] ?? ('Area ' . $card['area_no']) }}
                                </span>
                                @if(!empty($card['letters']))
                                <span class="badge bg-light text-secondary border px-1 py-0 ms-1" style="font-size:0.65rem;" title="Area Letter">{{ $card['letters'] }}</span>
                                @endif
                                @endif
                            </div>
                            <span class="badge {{ $card['progress_pct'] == 100 ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill font-monospace px-2 py-1" style="font-size:0.68rem;">
                                {{ $card['progress_pct'] }}%
                            </span>
                        </div>
                        
                        <div class="area-card-stat mt-1 pt-1 border-top">
                            <div class="d-flex justify-content-between text-muted" style="font-size:0.7rem;">
                                <span>Part: <strong class="text-dark font-monospace">{{ number_format($card['total_part']) }}</strong></span>
                                <span class="ms-1">Done: <strong class="text-success font-monospace">{{ number_format($card['total_returned']) }}</strong></span>
                            </div>
                            <div class="progress mt-1" style="height: 3px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $card['progress_pct'] }}%" aria-valuenow="{{ $card['progress_pct'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filter Box & Data Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-stopwatch text-primary me-2"></i> Rincian Timer Tiap Part & Step
                        </h5>
                        <div id="activeAreaBadge" class="mt-1 d-none">
                            <span class="badge bg-primary px-2 py-1">
                                <i class="fas fa-filter me-1"></i> Memfilter: Area <span id="activeAreaText">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshTable">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Data
                        </button>
                    </div>
                </div>

                <!-- Filter Tahapan Proses SO -->
                <div class="row g-2 mt-3 pt-3 border-top">
                    <div class="col-12 col-md-auto d-flex align-items-center">
                        <span class="small fw-bold text-muted me-2"><i class="fas fa-filter text-primary me-1"></i> Filter Proses:</span>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterCourier">
                            <option value="">Kurir: Semua</option>
                            <option value="done">Kurir: Sudah Selesai</option>
                            <option value="undone">Kurir: Belum Selesai</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterCount">
                            <option value="">Hitung: Semua</option>
                            <option value="done">Hitung: Sudah Selesai</option>
                            <option value="undone">Hitung: Belum Selesai</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterMethod">
                            <option value="">Metode: Semua</option>
                            <option value="manual">Metode: Manual (Pos 1 / 2)</option>
                            <option value="scale">Metode: Timbangan</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterReturn">
                            <option value="">Return: Semua</option>
                            <option value="done">Return: Sudah Selesai</option>
                            <option value="undone">Return: Belum Selesai</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-sm btn-light border text-muted w-100" id="btnResetFilter">
                            <i class="fas fa-times me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>

                <!-- Petunjuk Perhitungan -->
                <div class="alert alert-light border small mt-3 mb-0 text-muted" style="font-size:0.75rem;">
                    <i class="fas fa-info-circle text-primary me-1"></i>
                    <strong>Cara Kerja Timer Step:</strong> Timer pada tiap step adalah durasi waktu antar part yang discan oleh petugas tersebut. Contoh: jika Kurir A menscan part <strong>SA-A025</strong> pada 11.30 dan scan sebelumnya pada 11.25, maka durasi kurir untuk part tersebut adalah <strong>5 menit</strong>.
                </div>
            </div>

            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="timerDataTable" class="table table-striped table-bordered table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 3%;">No</th>
                                <th style="width: 7%;">Rak</th>
                                <th style="width: 5%;">Area</th>
                                <th style="width: 8%;">Cell</th>
                                <th style="width: 17%;">Part</th>
                                <th style="width: 11%;">Kurir</th>
                                <th style="width: 11%;">Pos 1</th>
                                <th style="width: 11%;">Pos 2</th>
                                <th style="width: 11%;">Timbang</th>
                                <th style="width: 10%;">Return</th>
                                <th style="width: 6%;">Siklus</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
<script>
$(document).ready(function() {
    let selectedAreaNo = null;

    function buildTimerDataUrl() {
        let url = "{{ route('admin.timers.data', $event->id) }}";
        if (selectedAreaNo !== null && selectedAreaNo !== '') {
            url += "?area_no=" + encodeURIComponent(selectedAreaNo);
        }
        return url;
    }

    const table = $('#timerDataTable').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: buildTimerDataUrl(),
            type: "GET",
            data: function(d) {
                d.filter_courier = $('#filterCourier').val();
                d.filter_count   = $('#filterCount').val();
                d.filter_method  = $('#filterMethod').val();
                d.filter_return  = $('#filterReturn').val();
            },
            error: function(xhr, error, code) {
                console.error("Timer DataTable error:", error, code);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { 
                data: 'rack_no', 
                name: 'rack_no', 
                render: function(data) {
                    return `<span class="fw-bold font-monospace text-primary">${data || '-'}</span>`;
                }
            },
            {
                data: 'area_no',
                name: 'area_no',
                className: 'text-center',
                render: function(data) {
                    if (!data || data === '-') return '<span class="text-muted">-</span>';
                    return `<span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold">Area ${data}</span>`;
                }
            },
            { 
                data: 'loc_display', 
                name: 'loc_display', 
                render: function(data) {
                    return `<div class="fw-semibold font-monospace">${data || '-'}</div>`;
                }
            },
            { 
                data: 'part_name', 
                name: 'part_name', 
                render: function(data, type, row) {
                    return `
                        <div>
                            <div class="fw-bold text-dark font-monospace">${row.item_code}</div>
                            <div class="text-muted small text-truncate" style="max-width:220px;" title="${data}">${data}</div>
                        </div>
                    `;
                }
            },
            { 
                data: 'courier_timer', 
                name: 'courier_seconds',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'pos1_timer', 
                name: 'pos1_seconds',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'pos2_timer', 
                name: 'pos2_seconds',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'scale_timer', 
                name: 'scale_seconds',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'return_timer', 
                name: 'return_seconds',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'cycle_timer', 
                name: 'cycle_seconds', 
                className: 'text-center',
                render: function(data) {
                    return data;
                }
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            search: "Cari Part / Rak / Cell:",
            searchPlaceholder: "Ketik kode part, rak, loc...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data timer yang sesuai",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data part",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    $('#btnRefreshTable').on('click', function() {
        table.ajax.url(buildTimerDataUrl()).load();
    });

    // Klik Card Area untuk filter list timer
    $('.area-card-box').on('click', function() {
        const areaNo = $(this).data('area-no');

        if (selectedAreaNo === areaNo) {
            // Jika diklik lagi, batalkan filter
            selectedAreaNo = null;
            $('.area-card-box').removeClass('border-primary shadow');
            $('#activeAreaBadge').addClass('d-none');
            $('#btnResetAreaFilter').addClass('d-none');
        } else {
            selectedAreaNo = areaNo;
            $('.area-card-box').removeClass('border-primary shadow');
            $(this).addClass('border-primary shadow');
            $('#activeAreaText').text(areaNo);
            $('#activeAreaBadge').removeClass('d-none');
            $('#btnResetAreaFilter').removeClass('d-none');
        }

        table.ajax.url(buildTimerDataUrl()).load();
    });

    // Reset Filter Area
    $('#btnResetAreaFilter').on('click', function() {
        selectedAreaNo = null;
        $('.area-card-box').removeClass('border-primary shadow');
        $('#activeAreaBadge').addClass('d-none');
        $(this).addClass('d-none');
        table.ajax.url(buildTimerDataUrl()).load();
    });

    // Filter Dropdown Proses (Kurir, Hitung, Metode, Return)
    $('#filterCourier, #filterCount, #filterMethod, #filterReturn').on('change', function() {
        table.draw();
    });

    // Reset Semua Filter
    $('#btnResetFilter').on('click', function() {
        $('#filterCourier').val('');
        $('#filterCount').val('');
        $('#filterMethod').val('');
        $('#filterReturn').val('');
        table.draw();
    });
});
</script>
@endsection
