@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">المستخدمون</h2>
        @can('users.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة مستخدم جديد
        </button>
        @endcan
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الأدوار</th>
                            <th>الفِرق</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr id="user-row-{{ $user->id }}">
                            <td class="fw-medium">{{ $user->username }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->roles && $user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-primary me-1">{{ $role->display_name ?? $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($user->teams && $user->teams->count() > 0)
                                    @foreach($user->teams as $team)
                                        <span class="badge me-1" style="background-color: {{ $team->color ?? '#3b82f6' }}20; color: {{ $team->color ?? '#3b82f6' }}; border: 1px solid {{ $team->color ?? '#3b82f6' }}40;">
                                            {{ $team->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('users.edit')
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser('{{ $user->id }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endcan
                                @can('users.delete')
                                @if($user->id !== Auth::id())
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser('{{ $user->id }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">إضافة مستخدم جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" id="userId" name="id">
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userUsername" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            كلمة المرور <span id="passwordRequired" class="text-danger">*</span>
                            <small id="passwordHint" class="text-muted d-none">(اتركه فارغاً للإبقاء على كلمة المرور الحالية)</small>
                        </label>
                        <input type="password" class="form-control" id="userPassword" name="password">
                    </div>
                    
                    <div class="mb-3" id="passwordConfirmationField">
                        <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="userPasswordConfirmation" name="password_confirmation">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الأدوار</label>
                        <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;">
                            @foreach($roles as $role)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="{{ $role->id }}" id="role_{{ $role->id }}" name="roles[]">
                                <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->display_name ?? $role->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الفِرق المسؤولة</label>
                        <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;">
                            @foreach($teams as $team)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="{{ $team->id }}" id="user_team_{{ $team->id }}" name="team_ids[]">
                                <label class="form-check-label d-flex align-items-center gap-2" for="user_team_{{ $team->id }}">
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
let usersData = @json($users);

function openUserModal() {
    $('#userModalTitle').text('إضافة مستخدم جديد');
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#passwordRequired').show();
    $('#passwordHint').addClass('d-none');
    $('#passwordConfirmationField').show();
    $('input[name="roles[]"], input[name="team_ids[]"]').prop('checked', false);
}

function editUser(id) {
    const user = usersData.find(u => u.id === id);
    if (!user) return;
    
    $('#userModalTitle').text('تعديل مستخدم');
    $('#userId').val(user.id);
    $('#userName').val(user.name);
    $('#userUsername').val(user.username);
    $('#userEmail').val(user.email);
    $('#userPassword').val('');
    $('#userPasswordConfirmation').val('');
    
    $('#passwordRequired').hide();
    $('#passwordHint').removeClass('d-none');
    $('#passwordConfirmationField').hide();
    
    $('input[name="roles[]"], input[name="team_ids[]"]').prop('checked', false);
    
    if (user.roles) {
        user.roles.forEach(function(role) {
            $(`#role_${role.id}`).prop('checked', true);
        });
    }
    
    if (user.teams) {
        user.teams.forEach(function(team) {
            $(`#user_team_${team.id}`).prop('checked', true);
        });
    }
    
    $('#userModal').modal('show');
}

$('#userForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#userName').val(),
        username: $('#userUsername').val(),
        email: $('#userEmail').val(),
        roles: $('input[name="roles[]"]:checked').map(function() {
            return $(this).val();
        }).get(),
        team_ids: $('input[name="team_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get()
    };
    
    const password = $('#userPassword').val();
    const userId = $('#userId').val();
    
    if (userId && !password) {
        // Don't send password if editing and empty
    } else if (!userId && !password) {
        showAlert('كلمة المرور مطلوبة', 'danger');
        return;
    } else {
        formData.password = password;
        formData.password_confirmation = $('#userPasswordConfirmation').val();
    }
    
    const url = userId 
        ? '{{ route("users.update", ":id") }}'.replace(':id', userId)
        : '{{ route("users.store") }}';
    const method = userId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            showAlert(response.message || (userId ? 'تم تحديث المستخدم بنجاح' : 'تم إضافة المستخدم بنجاح'), 'success');
            $('#userModal').modal('hide');
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || xhr.responseJSON?.error || 'حدث خطأ في حفظ المستخدم';
            showAlert(error, 'danger');
        }
    });
});

function deleteUser(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المستخدم؟')) return;
    
    $.ajax({
        url: '{{ route("users.destroy", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        success: function(response) {
            showAlert(response.message || 'تم حذف المستخدم بنجاح', 'success');
            $(`#user-row-${id}`).fadeOut(function() {
                $(this).remove();
            });
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'حدث خطأ في حذف المستخدم';
            showAlert(error, 'danger');
        }
    });
}

$('#userModal').on('hidden.bs.modal', function() {
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#passwordRequired').show();
    $('#passwordHint').addClass('d-none');
    $('#passwordConfirmationField').show();
    $('input[name="roles[]"], input[name="team_ids[]"]').prop('checked', false);
});
</script>
@endpush
@endsection

