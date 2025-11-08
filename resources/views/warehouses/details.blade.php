@extends('layouts.app')

@section('title', 'تفاصيل المخزن')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-right me-1"></i>
                العودة للمخازن
            </a>
            <h2 class="h3 fw-bold mb-0">{{ $warehouse->name }}</h2>
        </div>
        @can('transactions.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة حركة
        </button>
        @endcan
    </div>

    <!-- Warehouse Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>الموقع:</strong> {{ $warehouse->location }}</p>
                    @if($warehouse->manager)
                        <p class="mb-2"><strong>مدير المخزن:</strong> {{ $warehouse->manager }}</p>
                    @endif
                    @if($warehouse->manager_location)
                        <p class="mb-2"><strong>مكان المدير:</strong> {{ $warehouse->manager_location }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    @if($warehouse->description)
                        <p class="mb-2"><strong>الوصف:</strong> {{ $warehouse->description }}</p>
                    @endif
                    @if($warehouse->teams && $warehouse->teams->count() > 0)
                        <div>
                            <strong>الفِرق:</strong>
                            @foreach($warehouse->teams as $team)
                                <span class="badge me-1" style="background-color: {{ $team->color ?? '#3b82f6' }}20; color: {{ $team->color ?? '#3b82f6' }}; border: 1px solid {{ $team->color ?? '#3b82f6' }}40;">
                                    {{ $team->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-semibold">المخزون</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>اسم المنتج</th>
                            <th>الكود</th>
                            <th>الكمية المتوفرة</th>
                            <th>الوحدة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventory as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->product->name ?? 'غير معروف' }}</td>
                            <td>{{ $item->product->code ?? '' }}</td>
                            <td class="fw-bold text-primary">{{ number_format($item->quantity, 2) }}</td>
                            <td>{{ $item->product->unit ?? 'قطعة' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">لا توجد عناصر في المخزون</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة حركة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transactionForm">
                <div class="modal-body">
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <div class="mb-3">
                        <label class="form-label">نوع الحركة <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="in">دخول</option>
                            <option value="out">خروج</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">المنتج <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" required>
                            <option value="">اختر منتج</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" value="1" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">السيريال نمبر / الباركود</label>
                        <input type="text" class="form-control" name="serial_number" placeholder="4444 أو امسح الباركود">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$('#transactionForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        warehouse_id: '{{ $warehouse->id }}',
        product_id: $('select[name="product_id"]').val(),
        type: $('select[name="type"]').val(),
        quantity: parseFloat($('input[name="quantity"]').val()),
        serial_number: $('input[name="serial_number"]').val() || null,
        notes: $('textarea[name="notes"]').val() || null
    };
    
    $.ajax({
        url: '{{ route("transactions.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            showAlert(response.message || 'تمت إضافة الحركة بنجاح', 'success');
            $('#addTransactionModal').modal('hide');
            $('#transactionForm')[0].reset();
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || xhr.responseJSON?.message || 'حدث خطأ في إنشاء الحركة';
            showAlert(error, 'danger');
        }
    });
});
</script>
@endpush
@endsection

