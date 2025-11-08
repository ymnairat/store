@extends('layouts.app')

@section('title', 'المخزون')

@section('content')
<div class="fade-in">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h2 class="h3 fw-bold mb-0">المخزون</h2>
        <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <div class="input-group" style="min-width: 250px;">
                <span class="input-group-text bg-light">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control" id="searchInput" placeholder="ابحث عن منتج أو كود أو مخزن...">
            </div>
            <div class="input-group" style="min-width: 200px;">
                <span class="input-group-text bg-light">
                    <i class="bi bi-funnel"></i>
                </span>
                <select class="form-select" id="warehouseFilter">
                    <option value="">جميع المخازن</option>
                    @foreach($warehousesList as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouseId == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Inventory Cards -->
    <div class="row g-3" id="inventoryContainer">
        @forelse($inventory as $item)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-box fs-4 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title fw-semibold mb-1">{{ $item->product->name ?? 'غير معروف' }}</h5>
                            <p class="text-muted small mb-0">{{ $item->product->code ?? '' }}</p>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">المخزن:</span>
                            <span class="fw-medium">{{ $item->warehouse->name ?? 'غير معروف' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">الكمية المتوفرة:</span>
                            <span class="fw-bold fs-5 text-primary">{{ number_format($item->quantity, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">الوحدة:</span>
                            <span>{{ $item->product->unit ?? 'قطعة' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-4">لا توجد عناصر في المخزون</div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
$('#warehouseFilter').on('change', function() {
    const warehouseId = $(this).val();
    if (warehouseId) {
        window.location.href = '{{ route("inventory.index") }}?warehouseId=' + warehouseId;
    } else {
        window.location.href = '{{ route("inventory.index") }}';
    }
});

$('#searchInput').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.card').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).parent().toggle(text.includes(searchTerm));
    });
});
</script>
@endpush
@endsection

