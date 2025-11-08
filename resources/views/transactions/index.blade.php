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
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة حركة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transactionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نوع الحركة <span class="text-danger">*</span></label>
                        <select class="form-select" id="transactionType" name="type" required>
                            <option value="in">دخول</option>
                            <option value="out">خروج</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المخزن <span class="text-danger">*</span></label>
                        <select class="form-select" id="transactionWarehouse" name="warehouse_id" required>
                            <option value="">اختر مخزن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المنتج <span class="text-danger">*</span></label>
                        <select class="form-select" id="transactionProduct" name="product_id" required>
                            <option value="">اختر منتج</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="transactionQuantity" name="quantity" value="1" min="0.01" step="0.01" required>
                    </div>

                    <!-- Team Transfer Section (only for 'out' type) -->
                    <div id="teamTransferSection" class="mb-3 p-3 bg-light rounded border" style="display: none;">
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isReturnCheck">
                                <label class="form-check-label" for="isReturnCheck">
                                    إرجاع منتج تم نقله سابقاً
                                </label>
                            </div>
                        </div>
                        
                        <div id="teamTransferFields" style="display: none;">
                            <label class="form-label fw-semibold text-primary mb-2">نقل بين الفِرق (اختياري)</label>
                            <div class="mb-2">
                                <label class="form-label small">من فريق</label>
                                <select class="form-select form-select-sm" id="fromTeam" name="from_team_id">
                                    <option value="">اختر الفريق المصدر</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">إلى فريق</label>
                                <select class="form-select form-select-sm" id="toTeam" name="to_team_id" disabled>
                                    <option value="">اختر الفريق المصدر أولاً</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">السيريال نمبر / الباركود</label>
                        <input type="text" class="form-control" id="transactionSerial" name="serial_number" placeholder="4444 أو امسح الباركود">
                    </div>

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

$('#transactionType').on('change', function() {
    const isOut = $(this).val() === 'out';
    $('#teamTransferSection').toggle(isOut);
});

$('#fromTeam').on('change', function() {
    const fromTeamId = $(this).val();
    const toSelect = $('#toTeam');
    
    toSelect.prop('disabled', !fromTeamId);
    toSelect.html('<option value="">اختر الفريق الوجهة</option>');
    
    if (fromTeamId) {
        teamsData.forEach(function(team) {
            if (team.id !== fromTeamId) {
                toSelect.append(`<option value="${team.id}">${team.name}</option>`);
            }
        });
    }
});

$('#isReturnCheck').on('change', function() {
    $('#teamTransferFields').toggle(!$(this).is(':checked'));
});

$('#transactionForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        product_id: $('#transactionProduct').val(),
        warehouse_id: $('#transactionWarehouse').val(),
        type: $('#transactionType').val(),
        quantity: parseFloat($('#transactionQuantity').val()),
        serial_number: $('#transactionSerial').val() || null,
        notes: $('#transactionNotes').val() || null,
        is_return: $('#isReturnCheck').is(':checked')
    };
    
    if ($('#transactionType').val() === 'out' && $('#fromTeam').val() && $('#toTeam').val()) {
        formData.from_team_id = $('#fromTeam').val();
        formData.to_team_id = $('#toTeam').val();
    }
    
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

function deleteTransaction(id) {
    if (!confirm('هل أنت متأكد من حذف هذه الحركة؟')) return;
    
    $.ajax({
        url: '{{ route("transactions.destroy", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        success: function(response) {
            showAlert(response.message || 'تم حذف الحركة بنجاح', 'success');
            $(`#transaction-row-${id}`).fadeOut(function() {
                $(this).remove();
            });
        },
        error: function() {
            showAlert('حدث خطأ في حذف الحركة', 'danger');
        }
    });
}

$('#addTransactionModal').on('hidden.bs.modal', function() {
    $('#transactionForm')[0].reset();
});
</script>
@endpush
@endsection

