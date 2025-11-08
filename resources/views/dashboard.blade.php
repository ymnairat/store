@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="fade-in">
    <h2 class="h3 fw-bold mb-4">لوحة التحكم</h2>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">إجمالي المنتجات</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['products'] }}</h3>
                        </div>
                        <div class="bg-primary text-white rounded p-3">
                            <i class="bi bi-box fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">المخازن</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['warehouses'] }}</h3>
                        </div>
                        <div class="bg-success text-white rounded p-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">إجمالي القطع</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['totalItems'], 0) }}</h3>
                        </div>
                        <div class="bg-info text-white rounded p-3">
                            <i class="bi bi-clipboard-data fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-1">إجمالي الحركات</p>
                            <h3 class="mb-0 fw-bold">{{ $stats['transactions'] }}</h3>
                        </div>
                        <div class="bg-warning text-white rounded p-3">
                            <i class="bi bi-graph-up fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="card">
        <div class="card-body">
            <h3 class="h5 fw-semibold mb-3">مرحباً بك في نظام جرد المخازن</h3>
            <p class="text-muted mb-4">
                يمكنك إدارة المنتجات والمخازن ومتابعة المخزون والحركات من القائمة الجانبية.
            </p>
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="p-3 bg-primary bg-opacity-10 rounded">
                        <h4 class="h6 fw-semibold text-primary mb-2">إدارة المنتجات</h4>
                        <p class="small text-muted mb-0">أضف وعدّل المنتجات مع تفاصيلها الكاملة</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-success bg-opacity-10 rounded">
                        <h4 class="h6 fw-semibold text-success mb-2">إدارة المخازن</h4>
                        <p class="small text-muted mb-0">أنشئ وأدار المخازن المختلفة</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-info bg-opacity-10 rounded">
                        <h4 class="h6 fw-semibold text-info mb-2">متابعة المخزون</h4>
                        <p class="small text-muted mb-0">راجع الكميات المتوفرة في كل مخزن</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-warning bg-opacity-10 rounded">
                        <h4 class="h6 fw-semibold text-warning mb-2">سجل الحركات</h4>
                        <p class="small text-muted mb-0">تابع حركات الدخول والخروج</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

