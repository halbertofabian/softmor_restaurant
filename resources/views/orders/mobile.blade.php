@extends('layouts.pos')
@section('title', 'Comanda Móvil')
@section('navbar')
<nav
class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
id="layout-navbar">
    <div class="navbar-nav-left d-flex align-items-center" id="navbar-collapse">
        <a href="{{ route('tables.index') }}" class="text-body ms-2"><i class="ti tabler-arrow-left fs-3"></i></a>
        <h5 class="mb-0 fw-bold text-dark ms-3">Mesa {{ $order->table->name }}</h5>
    </div>
    <div class="navbar-nav-right d-flex align-items-center justify-content-end ms-auto" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            <span class="fw-bold text-primary">Modo Comanda</span>
        </div>
    </div>
</nav>
@endsection
@section('content')
<div class="d-flex flex-column h-100 bg-light">
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="ti tabler-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="ti tabler-alert-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <!-- Category Tabs (Sticky & Styled) -->
    <div class="bg-white border-bottom sticky-top z-1" style="top: 0px;">
        <div class="overflow-auto py-2 px-3 no-scrollbar" style="white-space: nowrap;">
            <ul class="nav nav-pills d-inline-flex" id="categoryTabs" role="tablist">
                <li class="nav-item me-2">
                    <button class="nav-link active rounded-pill px-3 py-1 small fw-bold" data-bs-toggle="tab" data-bs-target="#cat-all" type="button">Todo</button>
                </li>
                @foreach($categories as $category)
                <li class="nav-item me-2">
                    <button class="nav-link rounded-pill px-3 py-1 small fw-bold" data-bs-toggle="tab" data-bs-target="#cat-{{ $category->id }}" type="button">{{ $category->name }}</button>
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
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex flex-column cursor-pointer" data-bs-toggle="offcanvas" data-bs-target="#orderSummary">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Total</small>
                    <span class="h5 mb-0 fw-bold text-dark">${{ number_format($order->total, 2) }}</span>
                    <small class="text-primary fw-bold" style="font-size: 0.75rem;">
                        {{ $order->details->where('status', 'pending')->count() }} pendientes
                        <i class="ti tabler-chevron-up ms-1"></i>
                    </small>
                </div>
                
                <form action="{{ route('orders.send', $order) }}" method="POST" class="ms-3 flex-grow-1">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm d-flex justify-content-center align-items-center">
                        <span>Enviar</span>
                        <i class="ti tabler-send ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Summary Offcanvas -->
<div class="offcanvas offcanvas-bottom h-75 rounded-top-4" tabindex="-1" id="orderSummary">
    <div class="offcanvas-header border-bottom bg-light">
        <h5 class="offcanvas-title fw-bold">Tu Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        @if($order->details->isEmpty())
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
            <i class="ti tabler-basket display-4 mb-3 opacity-25"></i>
            <p>La comanda está vacía</p>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach($order->details as $detail)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-label-primary rounded-circle p-1 me-2" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">{{ $detail->quantity }}</span>
                            <span class="fw-bold">{{ $detail->product_name }}</span>
                        </div>
                        @if($detail->notes)
                        <div class="small text-muted fst-italic ms-4 mb-1"><i class="ti tabler-note me-1"></i>{{ $detail->notes }}</div>
                        @endif
                        <div class="ms-4">
                            @if($detail->status == 'pending')
                                <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 rounded-pill" style="font-size: 0.65rem;">Pendiente</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill" style="font-size: 0.65rem;">Enviado</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold mb-2">${{ number_format($detail->price * $detail->quantity, 2) }}</div>
                        @if($detail->status == 'pending')
                        <form action="{{ route('orders.remove-item', [$order, $detail]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="is_mobile" value="1">
                            <button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-2">Eliminar</button>
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
        <form id="addProductForm" action="{{ route('orders.add-item', $order) }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <input type="hidden" name="product_id" id="input_product_id">
            <input type="hidden" name="is_mobile" value="1">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalProductTitle">Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body pt-2">
                <p class="text-muted small mb-4">Personaliza este producto antes de agregarlo.</p>
                
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold small text-uppercase text-muted">Cantidad</label>
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <button class="btn btn-outline-secondary rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" type="button" onclick="adjustQty(-1)"><i class="ti tabler-minus"></i></button>
                        <input type="number" name="quantity" id="input_quantity" class="form-control form-control-lg text-center border-0 fw-bold fs-2" value="1" min="1" style="width: 80px;" required readonly>
                        <button class="btn btn-outline-primary rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" type="button" onclick="adjustQty(1)"><i class="ti tabler-plus"></i></button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Notas de Cocina</label>
                    <textarea name="notes" class="form-control bg-light border-0" rows="3" placeholder="Ej. Sin cebolla, Salsa aparte..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold">Agregar al Pedido</button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .nav-pills .nav-link.active {
        background-color: #000;
        color: #fff;
    }
    .nav-pills .nav-link {
        color: #000;
        background-color: #f5f5f9;
        transition: all 0.2s;
    }
</style>
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
</script>
@endsection

@endsection
