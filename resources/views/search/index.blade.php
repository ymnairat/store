@extends('layouts.app')

@section('title', 'البحث المتقدم')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold mb-0">البحث المتقدم</h2>
        <button class="btn btn-outline-secondary" onclick="resetFilters()">
            <i class="bi bi-arrow-clockwise me-2"></i>
            إعادة تعيين
        </button>
    </div>

    <!-- Search Type Selection -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i>
                <h5 class="mb-0 fw-semibold">نوع البحث</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <button class="btn w-100 p-3 border-2" id="searchTypeProducts" onclick="setSearchType('products')">
                        <i class="bi bi-box fs-4 text-primary d-block mb-2"></i>
                        <div class="fw-medium">البحث عن المنتجات</div>
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn w-100 p-3 border-2" id="searchTypeInventory" onclick="setSearchType('inventory')">
                        <i class="bi bi-building fs-4 text-success d-block mb-2"></i>
                        <div class="fw-medium">البحث في المخزون</div>
                    </button>
                </div>
                <div class="col-md-4">
                    <button class="btn w-100 p-3 border-2" id="searchTypeTransactions" onclick="setSearchType('transactions')">
                        <i class="bi bi-clipboard-data fs-4 text-info d-block mb-2"></i>
                        <div class="fw-medium">البحث في الحركات</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-search text-primary"></i>
                <h5 class="mb-0 fw-semibold">بحث</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" id="searchQueryLabel">اسم المنتج أو الكود</label>
                    <input type="text" class="form-control" id="searchQuery" placeholder="أدخل كلمة البحث...">
                </div>
                
                <div class="col-md-6" id="warehouseFilterField" style="display: none;">
                    <label class="form-label">المخزن</label>
                    <select class="form-select" id="warehouseFilter">
                        <option value="">جميع المخازن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">الفريق</label>
                    <select class="form-select" id="teamFilter">
                        <option value="">جميع الفِرق</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6" id="dateFilterField" style="display: none;">
                    <label class="form-label">الكمية المتوفرة بتاريخ (اختياري)</label>
                    <input type="date" class="form-control" id="dateFilter">
                </div>
                
                <div class="col-md-6" id="transactionTypeField" style="display: none;">
                    <label class="form-label">نوع الحركة</label>
                    <select class="form-select" id="transactionType">
                        <option value="">الكل</option>
                        <option value="in">داخل</option>
                        <option value="out">خارج</option>
                    </select>
                </div>
                
                <div class="col-md-6" id="serialNumberField" style="display: none;">
                    <label class="form-label">السيريال نمبر</label>
                    <input type="text" class="form-control" id="serialNumber" placeholder="ابحث بالسيريال نمبر...">
                </div>
            </div>
            
            <button class="btn btn-primary" id="searchBtn" onclick="handleSearch()">
                <i class="bi bi-search me-2"></i>
                <span id="searchBtnText">بحث</span>
            </button>
        </div>
    </div>

    <!-- Results -->
    <div class="card" id="resultsCard" style="display: none;">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-semibold">النتائج (<span id="resultsCount">0</span>)</h5>
        </div>
        <div class="card-body">
            <div id="resultsContainer"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let searchType = 'products';

function setSearchType(type) {
    searchType = type;
    
    $('[id^="searchType"]').removeClass('border-primary bg-primary bg-opacity-10').addClass('border-secondary');
    $(`#searchType${type.charAt(0).toUpperCase() + type.slice(1)}`).removeClass('border-secondary').addClass('border-primary bg-primary bg-opacity-10');
    
    const isInventory = type === 'inventory';
    const isTransactions = type === 'transactions';
    
    $('#warehouseFilterField').toggle(isInventory || isTransactions);
    $('#dateFilterField').toggle(isInventory);
    $('#transactionTypeField').toggle(isTransactions);
    $('#serialNumberField').toggle(isTransactions);
    
    if (type === 'products') {
        $('#searchQueryLabel').text('اسم المنتج أو الكود');
    } else if (type === 'inventory') {
        $('#searchQueryLabel').text('اسم المنتج أو الكود (اختياري)');
    } else {
        $('#searchQueryLabel').text('اسم المنتج أو الكود (اختياري)');
    }
    
    resetFilters();
}

function resetFilters() {
    $('#searchQuery').val('');
    $('#warehouseFilter').val('');
    $('#teamFilter').val('');
    $('#dateFilter').val('');
    $('#transactionType').val('');
    $('#serialNumber').val('');
    $('#resultsCard').hide();
}

function handleSearch() {
    const searchQuery = $('#searchQuery').val();
    const selectedWarehouse = $('#warehouseFilter').val();
    const selectedTeam = $('#teamFilter').val();
    const selectedDate = $('#dateFilter').val();
    const transactionType = $('#transactionType').val();
    const serialNumber = $('#serialNumber').val();
    
    if (!searchQuery.trim() && searchType !== 'inventory') {
        showAlert('الرجاء إدخال كلمة البحث', 'warning');
        return;
    }
    
    $('#searchBtn').prop('disabled', true);
    $('#searchBtnText').html('<span class="spinner-border spinner-border-sm me-2"></span>جاري البحث...');
    
    const params = new URLSearchParams();
    
    if (searchType === 'products') {
        params.append('search', searchQuery);
        if (selectedTeam) params.append('team_id', selectedTeam);
        
        $.ajax({
            url: '{{ route("search.products") }}?' + params.toString(),
            method: 'GET',
            success: function(data) {
                renderProductsResults(data);
            },
            error: function() {
                showAlert('حدث خطأ في البحث', 'danger');
            },
            complete: function() {
                $('#searchBtn').prop('disabled', false);
                $('#searchBtnText').html('بحث');
            }
        });
    } else if (searchType === 'inventory') {
        if (searchQuery) {
            $.ajax({
                url: '{{ route("search.products") }}?search=' + searchQuery,
                method: 'GET',
                success: function(productData) {
                    if (productData.length > 0) {
                        params.append('product_id', productData[0].id);
                    }
                    performInventorySearch(params, selectedWarehouse, selectedTeam, selectedDate);
                },
                error: function() {
                    performInventorySearch(params, selectedWarehouse, selectedTeam, selectedDate);
                }
            });
        } else {
            performInventorySearch(params, selectedWarehouse, selectedTeam, selectedDate);
        }
    } else if (searchType === 'transactions') {
        if (searchQuery) params.append('product_search', searchQuery);
        if (selectedWarehouse) params.append('warehouse_id', selectedWarehouse);
        if (transactionType) params.append('type', transactionType);
        if (serialNumber) params.append('serial_number', serialNumber);
        
        $.ajax({
            url: '{{ route("search.transactions") }}?' + params.toString(),
            method: 'GET',
            success: function(data) {
                renderTransactionsResults(data);
            },
            error: function() {
                showAlert('حدث خطأ في البحث', 'danger');
            },
            complete: function() {
                $('#searchBtn').prop('disabled', false);
                $('#searchBtnText').html('بحث');
            }
        });
    }
}

function performInventorySearch(params, warehouse, team, date) {
    if (warehouse) params.append('warehouse_id', warehouse);
    if (team) params.append('team_id', team);
    if (date) params.append('date', date);
    
    $.ajax({
        url: '{{ route("search.inventory") }}?' + params.toString(),
        method: 'GET',
        success: function(data) {
            renderInventoryResults(data);
        },
        error: function() {
            showAlert('حدث خطأ في البحث', 'danger');
        },
        complete: function() {
            $('#searchBtn').prop('disabled', false);
            $('#searchBtnText').html('بحث');
        }
    });
}

function renderProductsResults(results) {
    $('#resultsCount').text(results.length);
    
    if (results.length === 0) {
        $('#resultsContainer').html('<p class="text-muted text-center">لا توجد نتائج</p>');
        $('#resultsCard').show();
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>اسم المنتج</th><th>الكود</th><th>الوحدة</th><th>الفِرق</th></tr></thead><tbody>';
    
    results.forEach(function(product) {
        const teamsHtml = product.teams && product.teams.length > 0
            ? product.teams.map(team => {
                const color = team.color || '#3b82f6';
                return `<span class="badge me-1" style="background-color: ${color}20; color: ${color}; border: 1px solid ${color}40;">${team.name}</span>`;
            }).join('')
            : '-';
        
        html += `<tr><td class="fw-medium">${product.name}</td><td>${product.code}</td><td>${product.unit || 'قطعة'}</td><td>${teamsHtml}</td></tr>`;
    });
    
    html += '</tbody></table></div>';
    $('#resultsContainer').html(html);
    $('#resultsCard').show();
}

function renderInventoryResults(results) {
    $('#resultsCount').text(results.length);
    
    if (results.length === 0) {
        $('#resultsContainer').html('<p class="text-muted text-center">لا توجد نتائج</p>');
        $('#resultsCard').show();
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>اسم المنتج</th><th>الكود</th><th>المخزن</th><th>الكمية المتوفرة</th><th>الوحدة</th></tr></thead><tbody>';
    
    results.forEach(function(item) {
        html += `<tr>
            <td class="fw-medium">${item.product?.name || 'غير معروف'}</td>
            <td>${item.product?.code || ''}</td>
            <td>${item.warehouse?.name || 'غير معروف'}</td>
            <td class="fw-bold text-primary">${parseFloat(item.quantity).toFixed(2)}</td>
            <td>${item.product?.unit || 'قطعة'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    $('#resultsContainer').html(html);
    $('#resultsCard').show();
}

function renderTransactionsResults(results) {
    $('#resultsCount').text(results.length);
    
    if (results.length === 0) {
        $('#resultsContainer').html('<p class="text-muted text-center">لا توجد نتائج</p>');
        $('#resultsCard').show();
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>تاريخ الحركة</th><th>النوع</th><th>اسم المنتج</th><th>الكود</th><th>المخزن</th><th>الكمية</th><th>السيريال نمبر</th></tr></thead><tbody>';
    
    results.forEach(function(transaction) {
        const typeBadge = transaction.type === 'in' 
            ? '<span class="badge bg-success">داخل</span>' 
            : '<span class="badge bg-danger">خارج</span>';
        
        const date = new Date(transaction.created_at).toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `<tr>
            <td>${date}</td>
            <td>${typeBadge}</td>
            <td class="fw-medium">${transaction.product?.name || 'غير معروف'}</td>
            <td>${transaction.product?.code || ''}</td>
            <td>${transaction.warehouse?.name || 'غير معروف'}</td>
            <td>${parseFloat(transaction.quantity).toFixed(2)}</td>
            <td>${transaction.serial_number || '-'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    $('#resultsContainer').html(html);
    $('#resultsCard').show();
}

// Initialize
setSearchType('products');
</script>
@endpush
@endsection

