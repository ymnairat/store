@extends('layouts.app')

@section('title', 'الأدوار والصلاحيات')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">الأدوار والصلاحيات</h2>
        @can('roles.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="openRoleModal()">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة دور جديد
        </button>
        @endcan
    </div>

    <!-- Roles Grid -->
    <div class="row g-3">
        @forelse($roles as $role)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-shield-check fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-semibold mb-0">{{ $role->display_name ?? $role->name }}</h5>
                                <p class="text-muted small mb-0">{{ $role->name }}</p>
                            </div>
                        </div>
                        <div class="btn-group-vertical btn-group-sm">
                            @can('roles.edit')
                            <button class="btn btn-outline-primary btn-sm mb-1" onclick="editRole('{{ $role->id }}')" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                            @can('roles.delete')
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteRole('{{ $role->id }}')" title="حذف">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                    @if($role->description)
                        <p class="text-muted small mb-2">{{ $role->description }}</p>
                    @endif
                    <div>
                        <p class="small fw-medium text-muted mb-2">الصلاحيات:</p>
                        <div>
                            @if($role->permissions && $role->permissions->count() > 0)
                                @foreach($role->permissions as $perm)
                                    <span class="badge bg-success me-1 mb-1">{{ $perm->display_name ?? $perm->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted small">لا توجد صلاحيات</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-4">لا توجد أدوار</div>
        @endforelse
    </div>
</div>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalTitle">إضافة دور جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="roleForm">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" id="roleId" name="id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الدور (بالإنكليزي) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roleName" name="name" required placeholder="مثل: admin">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اسم العرض <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roleDisplayName" name="display_name" required placeholder="مثل: مدير عام">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" id="roleDescription" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الصلاحيات</label>
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            @foreach($groupedPermissions as $group => $perms)
                            <div class="mb-4">
                                <h6 class="fw-semibold text-dark mb-2">{{ $group }}</h6>
                                <div class="row g-2">
                                    @foreach($perms as $perm)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" name="permissions[]">
                                            <label class="form-check-label" for="perm_{{ $perm->id }}">{{ $perm->display_name ?? $perm->name }}</label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
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
let rolesData = @json($roles);

function openRoleModal() {
    $('#roleModalTitle').text('إضافة دور جديد');
    $('#roleForm')[0].reset();
    $('#roleId').val('');
    $('#roleName').prop('disabled', false);
    $('input[name="permissions[]"]').prop('checked', false);
}

function editRole(id) {
    const role = rolesData.find(r => r.id === id);
    if (!role) return;
    
    $('#roleModalTitle').text('تعديل دور');
    $('#roleId').val(role.id);
    $('#roleName').val(role.name);
    $('#roleName').prop('disabled', true);
    $('#roleDisplayName').val(role.display_name || role.name);
    $('#roleDescription').val(role.description || '');
    
    $('input[name="permissions[]"]').prop('checked', false);
    if (role.permissions) {
        role.permissions.forEach(function(perm) {
            $(`#perm_${perm.id}`).prop('checked', true);
        });
    }
    
    $('#roleModal').modal('show');
}

$('#roleForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#roleName').val(),
        display_name: $('#roleDisplayName').val(),
        description: $('#roleDescription').val() || null,
        permissions: $('input[name="permissions[]"]:checked').map(function() {
            return $(this).val();
        }).get()
    };
    
    const roleId = $('#roleId').val();
    const url = roleId 
        ? '{{ route("roles.update", ":id") }}'.replace(':id', roleId)
        : '{{ route("roles.store") }}';
    const method = roleId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            showAlert(response.message || (roleId ? 'تم تحديث الدور بنجاح' : 'تم إضافة الدور بنجاح'), 'success');
            $('#roleModal').modal('hide');
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || xhr.responseJSON?.message || 'حدث خطأ في حفظ الدور';
            showAlert(error, 'danger');
        }
    });
});

function deleteRole(id) {
    if (!confirm('هل أنت متأكد من حذف هذا الدور؟')) return;
    
    $.ajax({
        url: '{{ route("roles.destroy", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        success: function(response) {
            showAlert(response.message || 'تم حذف الدور بنجاح', 'success');
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function() {
            showAlert('حدث خطأ في حذف الدور', 'danger');
        }
    });
}

$('#roleModal').on('hidden.bs.modal', function() {
    $('#roleForm')[0].reset();
    $('#roleId').val('');
    $('#roleName').prop('disabled', false);
    $('input[name="permissions[]"]').prop('checked', false);
});
</script>
@endpush
@endsection

