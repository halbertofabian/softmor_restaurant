<div class="card h-100 cursor-pointer border-0 shadow-sm rounded-4 position-relative overflow-hidden" onclick="addProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
    <div class="card-body text-center p-3 d-flex flex-column align-items-center justify-content-center">
        <div class="avatar avatar-lg mb-3 bg-light rounded-circle d-flex align-items-center justify-content-center text-primary">
            <span class="fs-4 fw-bold">{{ substr($product->name, 0, 1) }}</span>
        </div>
        <h6 class="mb-1 text-dark fw-bold small text-truncate w-100">{{ $product->name }}</h6>
        <p class="mb-0 text-muted fw-bold small">${{ number_format($product->price, 2) }}</p>
    </div>
    <div class="position-absolute bottom-0 end-0 p-2">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 24px; height: 24px;">
            <i class="ti tabler-plus" style="font-size: 12px;"></i>
        </div>
    </div>
</div>
