@extends('layouts.main')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Auditor Dashboard</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="display-4">Hello World</h1>
                        <p class="lead">Selamat datang di halaman Auditor, {{ Auth::guard('auditor')->user()->name }}.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

