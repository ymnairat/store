@extends('layouts.app')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="login-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background-image: url('{{ asset('images/background.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed; position: relative;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom right, rgba(0,0,0,0.3), rgba(0,0,0,0.2), rgba(0,0,0,0.4));"></div>
    
    <div class="login-card" style="position: relative; z-index: 10; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); padding: 40px; max-width: 450px; width: 100%; margin: 20px;">
        <div class="text-center mb-4">
            <div class="mb-4">
                <img src="{{ asset('images/comet-logo.png') }}" alt="COMET Logo" class="img-fluid" style="max-height: 80px;" onerror="this.style.display='none'">
                <div class="d-none align-items-center justify-content-center bg-gradient text-white fw-bold fs-4 px-4 py-2 rounded shadow" style="background: linear-gradient(to bottom right, #d97706, #92400e);">
                    COMET
                </div>
            </div>
            <h1 class="h3 fw-bold text-dark mb-2">نظام جرد المخازن</h1>
            <p class="text-muted">تسجيل الدخول</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">اسم المستخدم أو البريد الإلكتروني</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="loginBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
$('#loginForm').on('submit', function(e) {
    const $btn = $('#loginBtn');
    $btn.prop('disabled', true);
    $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>جاري تسجيل الدخول...');
});
</script>
@endpush
@endsection

