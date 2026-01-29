<div class="card h-100 cursor-pointer border-0 shadow-lg rounded-4 position-relative overflow-hidden product-card-mobile" 
     onclick="addProduct({{ $product->id }}, '{{ addslashes($product->name) }}')"
     data-product-name="{{ strtolower($product->name) }}"
     style="background: linear-gradient(135deg, #18181b 0%, #27272a 100%); border: 1px solid rgba(255, 171, 29, 0.2) !important; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
    <div class="card-body text-center p-3 d-flex flex-column align-items-center justify-content-center">
        @if($product->image)
            <div class="mb-3" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255, 171, 29, 0.3);">
                <img src="{{ $product->image }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        @else
            <div class="avatar avatar-lg mb-3 rounded-circle d-flex align-items-center justify-content-center" 
                 style="background: linear-gradient(135deg, #FFAB1D 0%, #E59A1A 100%); width: 60px; height: 60px;">
                <span class="fs-4 fw-bold text-dark">{{ substr($product->name, 0, 1) }}</span>
            </div>
        @endif
        <h6 class="mb-2 fw-bold small text-truncate w-100" style="color: #fafafa;">{{ $product->name }}</h6>
        <p class="mb-0 fw-bold" style="color: #FFAB1D; font-size: 1.1rem;">${{ number_format($product->price, 2) }}</p>
    </div>
    <div class="position-absolute bottom-0 end-0 p-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-lg" 
             style="width: 32px; height: 32px; background: linear-gradient(135deg, #FFAB1D 0%, #E59A1A 100%);">
            <i class="ti tabler-plus text-dark" style="font-size: 16px; font-weight: bold;"></i>
        </div>
    </div>
</div>
