@extends('layouts.app')

@section('title', 'التقارير')

@section('content')
<div class="fade-in">
    <div class="mb-4">
        <h2 class="h3 fw-bold mb-0">التقارير</h2>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i>
                <h5 class="mb-0 fw-semibold">فلترة التقرير</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع التقرير</label>
                    <select class="form-select" id="reportType">
                        <option value="inventory">تقرير المخزون</option>
                        <option value="transactions">تقرير الحركات</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">
                        المخزن <span id="warehouseOptional" class="text-muted">(اختياري)</span>
                    </label>
                    <select class="form-select" id="warehouseFilter">
                        <option value="">جميع المخازن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">تاريخ البداية (اختياري)</label>
                    <input type="date" class="form-control" id="startDate">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">تاريخ النهاية (اختياري)</label>
                    <input type="date" class="form-control" id="endDate">
                </div>
                
                <div class="col-md-6" id="transactionTypeField" style="display: none;">
                    <label class="form-label">نوع الحركة</label>
                    <select class="form-select" id="transactionType">
                        <option value="">الكل</option>
                        <option value="in">داخل فقط</option>
                        <option value="out">خارج فقط</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Card -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-download text-success"></i>
                <h5 class="mb-0 fw-semibold">تصدير التقرير</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="text-center">
                <button class="btn btn-success btn-lg" id="exportBtn">
                    <i class="bi bi-download me-2"></i>
                    تصدير Excel (CSV)
                </button>
            </div>
            
            <div class="alert alert-info mt-4 mb-0">
                <h6 class="fw-semibold mb-2">معلومات التقرير:</h6>
                <ul class="mb-0 small">
                    <li><strong>تقرير المخزون:</strong> يعرض جميع المنتجات المتوفرة في المخازن مع الكميات</li>
                    <li><strong>تقرير الحركات:</strong> يعرض جميع حركات الدخول والخروج مع التفاصيل</li>
                    <li>يمكن تصدير التقرير لكل مخزن على حدة أو لجميع المخازن</li>
                    <li>التقارير تحترم صلاحيات المستخدم والفِرق المسؤول عنها</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$('#reportType').on('change', function() {
    const isTransactions = $(this).val() === 'transactions';
    $('#transactionTypeField').toggle(isTransactions);
    $('#warehouseOptional').toggleClass('d-none', !isTransactions);
});

$('#exportBtn').on('click', function() {
    const reportType = $('#reportType').val();
    const selectedWarehouse = $('#warehouseFilter').val();
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    const transactionType = $('#transactionType').val();
    
    let url = '';
    const params = new URLSearchParams();
    
    if (reportType === 'inventory') {
        if (selectedWarehouse) {
            url = '{{ route("reports.inventory.excel.warehouse", ":id") }}'.replace(':id', selectedWarehouse);
        } else {
            url = '{{ route("reports.inventory.excel") }}';
        }
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
    } else {
        url = '{{ route("reports.transactions.excel") }}';
        if (selectedWarehouse) params.append('warehouse_id', selectedWarehouse);
        if (transactionType) params.append('type', transactionType);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
    }
    
    const fullUrl = params.toString() ? `${url}?${params.toString()}` : url;
    window.location.href = fullUrl;
    
    showAlert('جاري تحميل التقرير...', 'info');
});
</script>
@endpush
@endsection

