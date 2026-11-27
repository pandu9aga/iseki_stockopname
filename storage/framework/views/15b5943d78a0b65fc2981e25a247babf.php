<?php $__env->startSection('style'); ?>
<style>
    .timer-header-card {
        background: linear-gradient(135deg, #0d3b66 0%, #001e3d 100%);
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="timer-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-stopwatch text-warning me-2"></i> Analisis Timer & Lead Time SO
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Pilih jadwal Stock Opname di bawah untuk menganalisis durasi per-step (Kurir, Hitung, dan Return ke Rak)
                    </p>
                </div>
                <div>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-outline-light fw-bold shadow-sm">
                        <i class="fas fa-calendar-alt me-1"></i> Kembali ke Jadwal SO
                    </a>
                </div>
            </div>

            <?php if($activeEvent): ?>
            <div class="alert alert-info bg-info bg-opacity-25 border-0 text-white mt-3 mb-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-play-circle fa-lg text-info me-2"></i>
                    <span>Jadwal Aktif Sedang Berjalan: <strong><?php echo e($activeEvent->event_name); ?> (<?php echo e($activeEvent->event_code); ?>)</strong></span>
                </div>
                <a href="<?php echo e(route('admin.timers.show', $activeEvent->id)); ?>" class="btn btn-sm btn-info fw-bold text-dark">
                    Buka Timer Event Aktif <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Event Cards Grid -->
        <div class="row g-3">
            <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card event-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge <?php echo e($ev->status === 'ACTIVE' ? 'bg-success' : ($ev->status === 'CLOSED' ? 'bg-secondary' : 'bg-warning text-dark')); ?> rounded-pill px-3 py-1">
                                    <?php echo e($ev->status); ?>

                                </span>
                                <small class="text-muted font-monospace"><?php echo e($ev->event_code); ?></small>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?php echo e($ev->event_name); ?></h5>
                            <p class="text-muted small mb-3">
                                <i class="far fa-calendar-alt me-1 text-primary"></i>
                                <?php echo e($ev->start_date ? $ev->start_date->format('d M Y') : '-'); ?> s/d <?php echo e($ev->end_date ? $ev->end_date->format('d M Y') : '-'); ?>

                            </p>

                            <div class="bg-light rounded p-2 mb-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total Part Target:</span>
                                    <strong class="text-dark"><?php echo e(number_format($ev->parts_count)); ?> part</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Part Diproses SO:</span>
                                    <strong class="text-primary"><?php echo e(number_format($ev->so_items_count)); ?> item</strong>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <a href="<?php echo e(route('admin.timers.show', $ev->id)); ?>" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    <i class="fas fa-stopwatch me-1"></i> Lihat Analisis Timer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12">
                    <div class="card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="fw-bold text-muted">Belum ada Jadwal Stock Opname</h5>
                            <p class="text-muted small">Silakan buat event Stock Opname terlebih dahulu melalui menu Jadwal SO.</p>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Buat Jadwal SO
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/admin/timers/index.blade.php ENDPATH**/ ?>