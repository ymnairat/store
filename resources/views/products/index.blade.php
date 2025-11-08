@extends('layouts.app')

@section('title', 'المنتجات')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">المنتجات</h2>
        @can('products.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openProductModal()">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة منتج جديد
        </button>
        @endcan
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control" id="searchInput" placeholder="ابحث عن منتج..." value="{{ request('search') }}">
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>اسم المنتج</th>
                            <th>الفئة/الفِرق</th>
                            <th>الوحدة</th>
                            <th>السعر</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        @forelse($products as $product)
                        <tr id="product-row-{{ $product->id }}">
                            <td>{{ $product->code }}</td>
                            <td class="fw-medium">{{ $product->name }}</td>
                            <td>
                                @if($product->teams && $product->teams->count() > 0)
                                    @foreach($product->teams as $team)
                                        <span class="badge me-1" style="background-color: {{ $team->color ?? '#3b82f6' }}20; color: {{ $team->color ?? '#3b82f6' }}; border: 1px solid {{ $team->color ?? '#3b82f6' }}40;">
                                            {{ $team->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">{{ $product->category ?? '-' }}</span>
                                @endif
                            </td>
                            <td>{{ $product->unit ?? 'قطعة' }}</td>
                            <td>{{ $product->price ? $product->price . ' د.أ' : '-' }}</td>
                            <td class="text-end">
                                @can('products.edit')
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct('{{ $product->id }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endcan
                                @can('products.delete')
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct('{{ $product->id }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا توجد منتجات</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">إضافة منتج جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" id="productId" name="id">
                    <div class="mb-3">
                        <label class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="productName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">رمز المنتج <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="productCode" name="code" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الفئات (الفِرق المسؤولة)</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @foreach($teams as $team)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="{{ $team->id }}" id="team_{{ $team->id }}" name="team_ids[]">
                                <label class="form-check-label d-flex align-items-center gap-2" for="team_{{ $team->id }}">
                                    <span class="rounded" style="width: 16px; height: 16px; background-color: {{ $team->color ?? '#3b82f6' }};"></span>
                                    <span>{{ $team->name }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الوحدة</label>
                            <select class="form-select" id="productUnit" name="unit">
                                <option value="قطعة">قطعة</option>
                                <option value="كيلو">كيلو</option>
                                <option value="لتر">لتر</option>
                                <option value="متر">متر</option>
                                <option value="صندوق">صندوق</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">السعر</label>
                            <input type="number" step="0.01" class="form-control" id="productPrice" name="price">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" id="productDescription" name="description" rows="3"></textarea>
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
let productsData = @json($products);
let teamsData = @json($teams);

// Search
$('#searchInput').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    if (searchTerm.length > 0) {
        window.location.href = '{{ route("products.index") }}?search=' + encodeURIComponent(searchTerm);
    } else {
        window.location.href = '{{ route("products.index") }}';
    }
});

// Open modal for new product
function openProductModal() {
    $('#productModalTitle').text('إضافة منتج جديد');
    $('#productForm')[0].reset();
    $('#productId').val('');
    $('input[name="team_ids[]"]').prop('checked', false);
}

// Edit product
function editProduct(id) {
    const product = productsData.find(p => p.id === id);
    if (!product) return;
    
    $('#productModalTitle').text('تعديل منتج');
    $('#productId').val(product.id);
    $('#productName').val(product.name);
    $('#productCode').val(product.code);
    $('#productUnit').val(product.unit || 'قطعة');
    $('#productPrice').val(product.price || '');
    $('#productDescription').val(product.description || '');
    
    // Reset checkboxes
    $('input[name="team_ids[]"]').prop('checked', false);
    
    // Check selected teams
    if (product.teams) {
        product.teams.forEach(function(team) {
            $(`#team_${team.id}`).prop('checked', true);
        });
    }
    
    $('#productModal').modal('show');
}

// Form submit
$('#productForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#productName').val(),
        code: $('#productCode').val(),
        unit: $('#productUnit').val(),
        price: $('#productPrice').val() || null,
        description: $('#productDescription').val() || null,
        team_ids: $('input[name="team_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get()
    };
    
    const productId = $('#productId').val();
    const url = productId 
        ? '{{ route("products.update", ":id") }}'.replace(':id', productId)
        : '{{ route("products.store") }}';
    const method = productId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            showAlert(response.message || (productId ? 'تم تحديث المنتج بنجاح' : 'تم إضافة المنتج بنجاح'), 'success');
            $('#productModal').modal('hide');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'حدث خطأ في حفظ المنتج';
            showAlert(error, 'danger');
        }
    });
});

// Delete product
function deleteProduct(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return;
    
    $.ajax({
        url: '{{ route("products.destroy", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        success: function(response) {
            showAlert(response.message || 'تم حذف المنتج بنجاح', 'success');
            $(`#product-row-${id}`).fadeOut(function() {
                $(this).remove();
            });
        },
        error: function() {
            showAlert('حدث خطأ في حذف المنتج', 'danger');
        }
    });
}

// Reset form when modal is closed
$('#productModal').on('hidden.bs.modal', function() {
    $('#productForm')[0].reset();
    $('#productId').val('');
    $('input[name="team_ids[]"]').prop('checked', false);
});
</script>
@endpush
@endsection

