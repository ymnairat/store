@extends('layouts.app')

@section('title', 'الحركات')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">الحركات</h2>
        @can('transactions.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة حركة جديدة
        </button>
        @endcan
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>نوع الحركة</th>
                            <th>المنتج</th>
                            <th>المخزن</th>
                            <th>الكمية</th>
                            <th>السيريال</th>
                            <th>التاريخ</th>
                            <th>الملاحظات</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr id="transaction-row-{{ $transaction->id }}">
                            <td>
                                @if($transaction->type === 'in')
                                    <span class="badge bg-success">دخول</span>
                                @else
                                    <span class="badge bg-danger">خروج</span>
                                @endif
                                @if($transaction->from_team && $transaction->to_team)
                                    <div class="small text-muted mt-1">نقل: {{ $transaction->from_team->name }} → {{ $transaction->to_team->name }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium">{{ $transaction->product->name ?? 'غير معروف' }}</div>
                                <small class="text-muted">{{ $transaction->product->code ?? '' }}</small>
                            </td>
                            <td>{{ $transaction->warehouse->name ?? 'غير معروف' }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->serial_number ?? '-' }}</td>
                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td class="text-truncate" style="max-width: 200px;" title="{{ $transaction->notes ?? '' }}">{{ $transaction->notes ?? '-' }}</td>
                            <td class="text-end">
                                @can('transactions.delete')
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction('{{ $transaction->id }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">لا توجد حركات</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="overflow: auto;">
            <div class="modal-header">
                <h5 class="modal-title">إضافة حركة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transactionForm">
                <div class="modal-body">

                    <!-- نوع الحركة -->
                    <div class="mb-3">
                        <label class="form-label">نوع الحركة <span class="text-danger">*</span></label>
                        <select class="form-select" id="transactionType" name="type" required>
                            <option value="in">دخول</option>
                            <option value="out">خروج</option>
                        </select>
                    </div>

                    <!-- المخزن -->
                    <div class="mb-3" id="warehouseSection">
                        <label class="form-label">المخزن <span class="text-danger">*</span></label>
                        <select class="form-select" id="transactionWarehouse" name="warehouse_id" required>
                            <option value="">اختر مخزن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- نقل بين المخازن -->
                    <div id="warehouseTransferSection" class="mb-3 p-3 bg-success bg-opacity-10 rounded border" style="display:none;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="isWarehouseTransfer">
                            <label class="form-check-label fw-semibold text-success" for="isWarehouseTransfer">
                                نقل بين المخازن
                            </label>
                        </div>

                        <div id="warehouseTransferFields" style="display:none;">
                            <div class="mb-2">
                                <label class="form-label small">من مخزن *</label>
                                <select class="form-select form-select-sm" id="warehouseFrom" name="warehouse_from_id">
                                    <option value="">اختر المخزن المصدر</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">إلى مخزن *</label>
                                <select class="form-select form-select-sm" id="warehouseTo" name="warehouse_to_id" disabled>
                                    <option value="">اختر المخزن المصدر أولاً</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-success small mt-1">💡 سيتم نقل المنتج من المخزن المصدر إلى المخزن الوجهة تلقائياً</p>
                    </div>

                    <!-- Team Transfer Section -->
                    <div id="teamTransferSection" class="mb-3 p-3 bg-primary bg-opacity-10 rounded border" style="display:none;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" disabled="true" type="checkbox" id="isReturnCheck">
                            <label class="form-check-label fw-semibold text-primary" for="isReturnCheck">
                                نقل بين الفرق
                            </label>
                        </div>

                        <div id="teamTransferFields">
                            <div class="mb-2">
                                <label class="form-label small">من فريق *</label>
                                <select class="form-select form-select-sm" id="fromTeam" name="from_team_id">
                                    <option value="">اختر الفريق المصدر</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">إلى فريق *</label>
                                <select class="form-select form-select-sm" id="toTeam" name="to_team_id" disabled>
                                    <option value="">اختر الفريق المصدر أولاً</option>
                                </select>
                            </div>
                            <p class="text-primary small mt-1">💡 سيتم نقل المنتج من الفريق المصدر إلى الفريق الوجهة تلقائياً</p>
                        </div>
                    </div>

                    <!-- المنتجات -->
                    <div class="mb-3">
                        <label class="form-label">المنتج <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select" id="transactionProduct" name="product_id" required>
                                <option value="">اختر منتج أو امسح الباركود</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="scanProductBtn" title="مسح باركود/QR Code للمنتج">
                                <i class="bi bi-upc-scan"></i>
                            </button>
                        </div>
                        <div id="selectedProductInfo" class="mt-2 p-2 bg-success bg-opacity-10 rounded" style="display:none;">
                            <strong id="selectedProductName"></strong>
                            <small id="selectedProductCode" class="d-block"></small>
                        </div>
                    </div>

                    <!-- الكمية -->
                    <div class="mb-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="transactionQuantity" name="quantity" value="1" min="0.01" step="0.01" required>
                    </div>

                    <!-- السيريال نمبر / الباركود -->
                    <div class="mb-3">
                        <label class="form-label">السيريال نمبر / الباركود</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="transactionSerial" name="serial_number" placeholder="4444 أو امسح الباركود">
                            <button type="button" class="btn btn-outline-secondary" id="scanSerialBtn" title="مسح السيريال نمبر">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <small class="text-muted">يمكنك إدخاله يدوياً أو مسحه من الباركود/QR Code</small>
                    </div>

                    <!-- الملاحظات -->
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="transactionNotes" name="notes" rows="3"></textarea>
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
let teamsData = @json($teams);
let allProductsData = @json($allProducts ?? $products);
let warehousesData = @json($warehouses);

// إظهار/إخفاء الأقسام حسب نوع الحركة
$('#transactionType').on('change', function(){
    const isOut = $(this).val() === 'out';
    $('#teamTransferSection').toggle(isOut);
    $('#warehouseTransferSection').toggle(isOut);
});

// تفعيل/تعطيل نقل بين المخازن
$('#isWarehouseTransfer').on('change', function(){
    $('#isReturnCheck').prop('checked', false);
    $('#isWarehouseTransfer').prop('disabled', true);
    $('#isReturnCheck').prop('disabled', false);
    $('#teamTransferFields').toggle(!$(this).is(':checked'));
    $('#warehouseTransferFields').toggle($(this).is(':checked'));
});

// تفعيل/تعطيل حقل المخزن الوجهة
$('#warehouseFrom').on('change', function(){
    const fromId = $(this).val();
    const toSelect = $('#warehouseTo');
    toSelect.prop('disabled', !fromId);
    toSelect.html('<option value="">اختر المخزن الوجهة</option>');

    if(fromId){
        warehousesData.forEach(w => {
            if(w.id != fromId) toSelect.append(`<option value="${w.id}">${w.name}</option>`);
        });
    }
});

// إظهار/إخفاء نقل الفرق عند الإرجاع
$('#isReturnCheck').on('change', function(){
    $('#isWarehouseTransfer').prop('checked', false);
    $('#isWarehouseTransfer').prop('disabled', false);
    $('#isReturnCheck').prop('disabled', true);
    $('#warehouseTransferFields').toggle(!$(this).is(':checked'));
    $('#teamTransferFields').toggle($(this).is(':checked'));
});

// تفعيل/تعطيل الفرق الوجهة
$('#fromTeam').on('change', function(){
    const fromId = $(this).val();
    const toSelect = $('#toTeam');
    toSelect.prop('disabled', !fromId);
    toSelect.html('<option value="">اختر الفريق الوجهة</option>');

    if(fromId){
        teamsData.forEach(t => { if(t.id != fromId) toSelect.append(`<option value="${t.id}">${t.name}</option>`); });
        // فلترة المنتجات حسب الفريق
        const filteredProducts = allProductsData.filter(p => p.teams?.some(t => t.id == fromId));
        const productSelect = $('#transactionProduct');
        productSelect.html('<option value="">اختر منتج أو امسح الباركود</option>');
        filteredProducts.forEach(p => productSelect.append(`<option value="${p.id}">${p.name} (${p.code})</option>`));
    }
});

// عرض المنتج المختار
$('#transactionProduct').on('change', function(){
    const id = $(this).val();
    const product = allProductsData.find(p => p.id == id);
    if(product){
        $('#selectedProductName').text(product.name);
        $('#selectedProductCode').text(product.code);
        $('#selectedProductInfo').show();
    } else $('#selectedProductInfo').hide();
});

// إرسال الفورم
$('#transactionForm').on('submit', function(e){
    e.preventDefault();
    let formData = {
        type: $('#transactionType').val(),
        warehouse_id: $('#transactionWarehouse').val(),
        product_id: $('#transactionProduct').val(),
        quantity: parseFloat($('#transactionQuantity').val()),
        serial_number: $('#transactionSerial').val() || null,
        notes: $('#transactionNotes').val() || null,
        is_return: $('#isReturnCheck').is(':checked')
    };

    if($('#isWarehouseTransfer').is(':checked')){
        formData.warehouse_from_id = $('#warehouseFrom').val();
        formData.warehouse_to_id = $('#warehouseTo').val();
    }

    if($('#transactionType').val() === 'out'){
        if($('#fromTeam').val()) formData.from_team_id = $('#fromTeam').val();
        if($('#toTeam').val()) formData.to_team_id = $('#toTeam').val();
    }

    $.ajax({
        url: '{{ route("transactions.store") }}',
        method: 'POST',
        data: formData,
        success: function(res){
            alert(res.message || 'تمت إضافة الحركة بنجاح');
            $('#addTransactionModal').modal('hide');
            $('#transactionForm')[0].reset();
            $('#teamTransferFields, #warehouseTransferFields, #selectedProductInfo').hide();
            setTimeout(()=> location.reload(), 800);
        },
        error: function(xhr){
            alert(xhr.responseJSON?.message || 'حدث خطأ');
        }
    });
});
</script>

@endpush
@endsection

