@extends('layouts.pos')
@section('title', 'Comanda Móvil')
@section('navbar')
<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center" 
     id="layout-navbar"
     style="background: #09090b !important; border-bottom: 1px solid rgba(255, 171, 29, 0.2);">
    <div class="navbar-nav-left d-flex align-items-center" id="navbar-collapse">
        <a href="{{ route('tables.index') }}" class="text-body ms-2" style="color: #FFAB1D !important;">
            <i class="ti tabler-arrow-left fs-3"></i>
        </a>
        <h5 class="mb-0 fw-bold ms-3" style="color: #fafafa;">Mesa {{ $order->table->name }}</h5>
    </div>
    <div class="navbar-nav-right d-flex align-items-center justify-content-end ms-auto" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            <span class="fw-bold" style="color: #FFAB1D;">Modo Comanda</span>
        </div>
    </div>
</nav>
@endsection
@section('content')
<style>
    :root {
        --primary: #FFAB1D;
        --primary-dark: #E59A1A;
        --dark-bg: #09090b;
        --card-bg: #18181b;
        --sidebar-bg: #0f0f10;
        --border-subtle: rgba(255, 171, 29, 0.2);
        --text-primary: #fafafa;
        --text-secondary: #a1a1a1;
    }
    
    body {
        background-color: var(--dark-bg) !important;
    }
    
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: #000;
        font-weight: bold;
    }
    .nav-pills .nav-link {
        color: var(--text-secondary);
        background-color: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s;
    }
    .nav-pills .nav-link:hover {
        background-color: rgba(255, 171, 29, 0.1);
        border-color: rgba(255, 171, 29, 0.3);
    }
    
    .product-card-mobile:hover {
        transform: translateY(-4px);
        border-color: var(--primary) !important;
        box-shadow: 0 12px 28px rgba(255, 171, 29, 0.3) !important;
    }
</style>

<div class="d-flex flex-column h-100" style="background-color: var(--dark-bg);">
    
    @if(session('success'))
    <div class="alert alert-dismissible fade show m-3 mb-0" role="alert" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;">
        <i class="ti tabler-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-dismissible fade show m-3 mb-0" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171;">
        <i class="ti tabler-alert-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <!-- Search Bar & Category Tabs (Sticky) -->
    <div class="border-bottom sticky-top z-1" style="top: 0px; background: var(--dark-bg); border-bottom: 1px solid var(--border-subtle) !important;">
        <!-- Search Bar -->
        <div class="p-3 pb-2">
            <div class="input-group" style="box-shadow: 0 4px 12px rgba(255, 171, 29, 0.15);">
                <span class="input-group-text" style="background: var(--card-bg); border: 1px solid var(--border-subtle); border-right: none; color: var(--primary);">
                    <i class="ti tabler-search" style="font-size: 20px;"></i>
                </span>
                <input 
                    type="text" 
                    class="form-control fw-bold" 
                    style="background: var(--card-bg); border: 1px solid var(--border-subtle); border-left: none; color: var(--text-primary); font-size: 1rem;"
                    id="product-search" 
                    placeholder="Buscar productos..."
                    oninput="searchProducts()"
                >
            </div>
        </div>
        
        <!-- Categories -->
        <div class="overflow-auto py-2 px-3 no-scrollbar" style="white-space: nowrap;">
            <ul class="nav nav-pills d-inline-flex" id="categoryTabs" role="tablist">
                <li class="nav-item me-2">
                    <button class="nav-link active rounded-pill px-3 py-1 small fw-bold" 
                            data-bs-toggle="tab" 
                            data-bs-target="#cat-all" 
                            type="button"
                            onclick="clearSearch()">Todo</button>
                </li>
                @foreach($categories as $category)
                <li class="nav-item me-2">
                    <button class="nav-link rounded-pill px-3 py-1 small fw-bold" 
                            data-bs-toggle="tab" 
                            data-bs-target="#cat-{{ $category->id }}" 
                            type="button"
                            onclick="clearSearch()">{{ $category->name }}</button>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="tab-content flex-grow-1 overflow-auto p-3 mb-5" style="padding-bottom: 100px !important;">
        <div class="tab-pane fade show active" id="cat-all">
            <div class="row g-3">
                @foreach($products as $product)
                <div class="col-6 col-md-4">
                    @include('orders.partials.mobile-card', ['product' => $product])
                </div>
                @endforeach
            </div>
        </div>

        @foreach($categories as $category)
        <div class="tab-pane fade" id="cat-{{ $category->id }}">
            <div class="row g-3">
                @foreach($products->where('category_id', $category->id) as $product)
                <div class="col-6 col-md-4">
                    @include('orders.partials.mobile-card', ['product' => $product])
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <!-- Glassmorphism Bottom Bar -->
    <div class="fixed-bottom p-3">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" 
             style="background: linear-gradient(135deg, rgba(24, 24, 27, 0.95) 0%, rgba(39, 39, 42, 0.95) 100%); 
                    backdrop-filter: blur(10px); 
                    border: 1px solid var(--border-subtle) !important;">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex flex-column cursor-pointer" data-bs-toggle="offcanvas" data-bs-target="#orderSummary">
                    <small class="fw-bold text-uppercase" style="font-size: 0.65rem; color: var(--text-secondary);">Total</small>
                    <span class="h5 mb-0 fw-bold" style="color: var(--primary);">${{ number_format($order->total, 2) }}</span>
                    <small class="fw-bold" style="font-size: 0.75rem; color: var(--text-secondary);">
                        {{ $order->details->where('status', 'pending')->count() }} pendientes
                        <i class="ti tabler-chevron-up ms-1"></i>
                    </small>
                </div>
                
                <form action="{{ route('orders.send', $order) }}" method="POST" class="ms-3 flex-grow-1">
                    @csrf
                    <button type="submit" class="btn w-100 rounded-pill py-2 fw-bold shadow-sm d-flex justify-content-center align-items-center"
                            style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #000;">
                        <span>Enviar</span>
                        <i class="ti tabler-send ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Summary Offcanvas -->
<div class="offcanvas offcanvas-bottom h-75 rounded-top-4" tabindex="-1" id="orderSummary" 
     style="background: var(--card-bg); border-top: 2px solid var(--border-subtle);">
    <div class="offcanvas-header border-bottom" style="background: var(--dark-bg); border-bottom: 1px solid var(--border-subtle) !important;">
        <h5 class="offcanvas-title fw-bold" style="color: var(--text-primary);">Tu Pedido</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        @if($order->details->isEmpty())
        <div class="d-flex flex-column align-items-center justify-content-center h-100" style="color: var(--text-secondary);">
            <i class="ti tabler-basket display-4 mb-3 opacity-25"></i>
            <p>La comanda está vacía</p>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach($order->details as $detail)
            <div class="list-group-item py-3" style="background: var(--card-bg); border-color: var(--border-subtle) !important;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge rounded-circle p-1 me-2" 
                                  style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #000;">{{ $detail->quantity }}</span>
                            <span class="fw-bold" style="color: var(--text-primary);">{{ $detail->product_name }}</span>
                        </div>
                        @if($detail->notes)
                        <div class="small fst-italic ms-4 mb-1" style="color: var(--text-secondary);">
                            <i class="ti tabler-note me-1"></i>{{ $detail->notes }}
                        </div>
                        @endif
                        <div class="ms-4">
                            @if($detail->status == 'pending')
                                <span class="badge px-2 py-1 rounded-pill" style="font-size: 0.65rem; background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);">Pendiente</span>
                            @else
                                <span class="badge px-2 py-1 rounded-pill" style="font-size: 0.65rem; background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">Enviado</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold mb-2" style="color: var(--primary);">${{ number_format($detail->price * $detail->quantity, 2) }}</div>
                        @if($detail->status == 'pending')
                        <form action="{{ route('orders.remove-item', [$order, $detail]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="is_mobile" value="1">
                            <button type="submit" class="btn btn-xs rounded-pill px-2" 
                                    style="background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Eliminar</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form id="addProductForm" action="{{ route('orders.add-item', $order) }}" method="POST" 
              class="modal-content rounded-4 border-0 shadow" 
              style="background: var(--card-bg); border: 1px solid var(--border-subtle) !important;">
            @csrf
            <input type="hidden" name="product_id" id="input_product_id">
            <input type="hidden" name="is_mobile" value="1">
            
            <div class="modal-header border-0 pb-0" style="border-bottom: 1px solid var(--border-subtle) !important;">
                <h5 class="modal-title fw-bold" id="modalProductTitle" style="color: var(--text-primary);">Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body pt-2">
                <p class="small mb-4" style="color: var(--text-secondary);">Personaliza este producto antes de agregarlo.</p>
                
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold small text-uppercase" style="color: var(--text-secondary);">Cantidad</label>
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <button class="btn rounded-circle p-0 d-flex align-items-center justify-content-center" 
                                style="width: 40px; height: 40px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-subtle); color: var(--text-primary);" 
                                type="button" onclick="adjustQty(-1)">
                            <i class="ti tabler-minus"></i>
                        </button>
                        <input type="number" name="quantity" id="input_quantity" 
                               class="form-control form-control-lg text-center border-0 fw-bold fs-2" 
                               style="width: 80px; background: transparent; color: var(--primary);" 
                               value="1" min="1" required readonly>
                        <button class="btn rounded-circle p-0 d-flex align-items-center justify-content-center" 
                                style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border: none; color: #000;" 
                                type="button" onclick="adjustQty(1)">
                            <i class="ti tabler-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase" style="color: var(--text-secondary);">Notas de Cocina</label>
                    <textarea name="notes" class="form-control border-0" rows="3" 
                              style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--border-subtle) !important;" 
                              placeholder="Ej. Sin cebolla, Salsa aparte..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn rounded-pill px-4" 
                        style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--border-subtle);" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn rounded-pill px-4 flex-grow-1 fw-bold" 
                        style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #000;">
                    Agregar al Pedido
                </button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
function addProduct(id, name) {
    document.getElementById('input_product_id').value = id;
    document.getElementById('modalProductTitle').innerText = name;
    document.getElementById('input_quantity').value = 1;
    document.querySelector('textarea[name="notes"]').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    myModal.show();
}

function adjustQty(amount) {
    let input = document.getElementById('input_quantity');
    let current = parseInt(input.value) || 0;
    let newValue = current + amount;
    if(newValue < 1) newValue = 1;
    input.value = newValue;
}

function searchProducts() {
    const searchTerm = document.getElementById('product-search').value.toLowerCase();
    const items = document.querySelectorAll('.product-card-mobile');
    
    items.forEach(item => {
        const productName = item.dataset.productName;
        const parent = item.closest('.col-6, .col-md-4');
        
        if (productName.includes(searchTerm)) {
            parent.style.display = 'block';
        } else {
            parent.style.display = 'none';
        }
    });
}

function clearSearch() {
    document.getElementById('product-search').value = '';
    searchProducts();
}
</script>
@endsection

@endsection
