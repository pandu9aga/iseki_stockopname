@extends('layouts.main')

@section('style')
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
@endsection

@section('content')
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
                    <a class="nav-link" id="admin-tab" data-bs-toggle="pill" href="#admin" role="tab">Admin</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Member Login -->
                <div class="tab-pane fade show active" id="member" role="tabpanel">
                    <form action="{{ route('login.member') }}" method="POST">
                        @csrf
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

                <!-- Admin Login -->
                <div class="tab-pane fade" id="admin" role="tabpanel">
                    <form action="{{ route('login.admin') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Input Username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Input Password" required>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Login Admin</button>
                    </form>
                </div>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
