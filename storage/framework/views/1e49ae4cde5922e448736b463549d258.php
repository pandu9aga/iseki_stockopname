<?php $__env->startSection('style'); ?>
<style>
    .login-container {
        max-width: 400px;
        margin: 100px auto;
    }
    .nav-pills .nav-link.active {
        background-color: #6861ce;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 0;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="login-container">
    <div class="card shadow-sm">
        <div class="card-header text-center pt-4">
            <h3 class="fw-bold">Login Stockopname</h3>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills nav-justified mb-4" id="loginTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="member-tab" data-bs-toggle="pill" href="#member" role="tab">Member</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="auditor-tab" data-bs-toggle="pill" href="#auditor" role="tab">Auditor</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="admin-tab" data-bs-toggle="pill" href="#admin" role="tab">Admin</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Member Login -->
                <div class="tab-pane fade show active" id="member" role="tabpanel">
                    <form action="<?php echo e(route('login.member')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-control" placeholder="Input NIK" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Input Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login Member</button>
                    </form>
                </div>

                <!-- Auditor Login -->
                <div class="tab-pane fade" id="auditor" role="tabpanel">
                    <form action="<?php echo e(route('login.auditor')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="name" class="form-control" placeholder="Input Username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Input Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login Auditor</button>
                    </form>
                </div>

                <!-- Admin Login -->
                <div class="tab-pane fade" id="admin" role="tabpanel">
                    <form action="<?php echo e(route('login.admin')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Input Name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Input Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login Admin</button>
                    </form>
                </div>
            </div>

            <?php if($errors->any()): ?>
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/auth/login.blade.php ENDPATH**/ ?>