@extends('layouts.app')

@section('title', 'المخازن')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">المخازن</h2>
        @can('warehouses.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="openWarehouseModal()">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة مخزن جديد
        </button>
        @endcan
    </div>

    <!-- Warehouses Grid -->
    <div class="row g-3" id="warehousesContainer">
        @forelse($warehouses as $warehouse)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h5 class="card-title fw-semibold">{{ $warehouse->name }}</h5>
                            <p class="text-muted small mb-1">{{ $warehouse->location }}</p>
                            @if($warehouse->manager)
                                <p class="text-primary small mb-1">
                                    <strong>مدير المخزن:</strong> {{ $warehouse->manager }}
                                </p>
                            @endif
                            @if($warehouse->manager_location)
                                <p class="text-muted small mb-1">
                                    <strong>مكان المدير:</strong> {{ $warehouse->manager_location }}
                                </p>
                            @endif
                        </div>
                        <div class="btn-group-vertical btn-group-sm">
                            @can('warehouses.view')
                            <a href="{{ route('warehouses.details', $warehouse->id) }}" class="btn btn-outline-success btn-sm mb-1" title="عرض التفاصيل">
                                <i class="bi bi-eye"></i>
                            </a>
                            @endcan
                            @can('warehouses.edit')
                            <button class="btn btn-outline-primary btn-sm mb-1" onclick="editWarehouse('{{ $warehouse->id }}')" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                            @can('warehouses.delete')
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteWarehouse('{{ $warehouse->id }}')" title="حذف">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                    @if($warehouse->description)
                        <p class="text-muted small mb-2">{{ $warehouse->description }}</p>
                    @endif
                    @if($warehouse->teams && $warehouse->teams->count() > 0)
                        <div class="mt-2">
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
        @empty
        <div class="col-12 text-center text-muted py-4">لا توجد مخازن</div>
        @endforelse
    </div>
</div>

<!-- Warehouse Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="warehouseModalTitle">إضافة مخزن جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="warehouseForm">
                <div class="modal-body">
                    <input type="hidden" id="warehouseId" name="id">
                    <div class="mb-3">
                        <label class="form-label">اسم المخزن <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="warehouseName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الموقع <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="warehouseLocation" name="location" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اسم مدير المخزن</label>
                        <input type="text" class="form-control" id="warehouseManager" name="manager">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">مكان/موقع المدير</label>
                        <input type="text" class="form-control" id="warehouseManagerLocation" name="manager_location">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" id="warehouseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الفِرق المسؤولة</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @foreach($teams as $team)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="{{ $team->id }}" id="warehouse_team_{{ $team->id }}" name="team_ids[]">
                                <label class="form-check-label d-flex align-items-center gap-2" for="warehouse_team_{{ $team->id }}">
                                    <span class="rounded" style="width: 16px; height: 16px; background-color: {{ $team->color ?? '#3b82f6' }};"></span>
                                    <span>{{ $team->name }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
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
let warehousesData = @json($warehouses);

function openWarehouseModal() {
    $('#warehouseModalTitle').text('إضافة مخزن جديد');
    $('#warehouseForm')[0].reset();
    $('#warehouseId').val('');
    $('input[name="team_ids[]"]').prop('checked', false);
}

function editWarehouse(id) {
    const warehouse = warehousesData.find(w => w.id === id);
    if (!warehouse) return;
    
    $('#warehouseModalTitle').text('تعديل مخزن');
    $('#warehouseId').val(warehouse.id);
    $('#warehouseName').val(warehouse.name);
    $('#warehouseLocation').val(warehouse.location);
    $('#warehouseManager').val(warehouse.manager || '');
    $('#warehouseManagerLocation').val(warehouse.manager_location || '');
    $('#warehouseDescription').val(warehouse.description || '');
    
    $('input[name="team_ids[]"]').prop('checked', false);
    if (warehouse.teams) {
        warehouse.teams.forEach(function(team) {
            $(`#warehouse_team_${team.id}`).prop('checked', true);
        });
    }
    
    $('#warehouseModal').modal('show');
}

$('#warehouseForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#warehouseName').val(),
        location: $('#warehouseLocation').val(),
        manager: $('#warehouseManager').val() || null,
        manager_location: $('#warehouseManagerLocation').val() || null,
        description: $('#warehouseDescription').val() || null,
        team_ids: $('input[name="team_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get()
    };
    
    const warehouseId = $('#warehouseId').val();
    const url = warehouseId 
        ? '{{ route("warehouses.update", ":id") }}'.replace(':id', warehouseId)
        : '{{ route("warehouses.store") }}';
    const method = warehouseId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            showAlert(response.message || (warehouseId ? 'تم تحديث المخزن بنجاح' : 'تم إضافة المخزن بنجاح'), 'success');
            $('#warehouseModal').modal('hide');
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'حدث خطأ في حفظ المخزن';
            showAlert(error, 'danger');
        }
    });
});

function deleteWarehouse(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المخزن؟')) return;
    
    $.ajax({
        url: '{{ route("warehouses.destroy", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        success: function(response) {
            showAlert(response.message || 'تم حذف المخزن بنجاح', 'success');
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function() {
            showAlert('حدث خطأ في حذف المخزن', 'danger');
        }
    });
}

$('#warehouseModal').on('hidden.bs.modal', function() {
    $('#warehouseForm')[0].reset();
    $('#warehouseId').val('');
    $('input[name="team_ids[]"]').prop('checked', false);
});
</script>
@endpush
@endsection

