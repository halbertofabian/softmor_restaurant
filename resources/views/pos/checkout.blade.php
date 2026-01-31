<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - {{ $order->table->name ?? 'Mesa' }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #FFAB1D;
            --primary-dark: #E59A1A;
            --dark-bg: #09090b;
            --card-bg: #18181b;
            --sidebar-bg: #0f0f10;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --text-primary: #fafafa;
            --text-secondary: #a1a1a1;
        }

        body {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background-color: var(--dark-bg);
            color: var(--text-primary);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            height: 100%;
            max-height: 100vh;
        }

        .checkout-sidebar {
            background: var(--sidebar-bg);
            border-left: 1px solid var(--border-subtle);
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.3);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .product-card {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--card-bg);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(255, 171, 29, 0.2);
        }

        .product-card:active {
            transform: translateY(-2px);
        }

        .order-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            min-height: 200px;
            max-height: 60vh;
        }

        .category-box {
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 110px;
            background: var(--card-bg);
            border: 2px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 1rem;
        }

        .category-box:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 171, 29, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .category-active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
            color: #000 !important;
            border-color: var(--primary) !important;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(255, 171, 29, 0.4);
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Custom Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 171, 29, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Firefox Support */
        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 171, 29, 0.3) transparent;
        }

        .header-section {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-subtle);
            padding: 1.25rem 1.5rem;
        }

        .order-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .order-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 171, 29, 0.2);
        }

        .payment-method-btn {
            border: 2px solid var(--border-subtle);
            background: var(--card-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
            padding: 1rem;
            border-radius: 0.75rem;
        }

        .payment-method-btn:hover {
            border-color: var(--primary);
            background: rgba(255, 171, 29, 0.1);
            transform: translateY(-2px);
        }

        .btn-check:checked + .payment-method-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-color: var(--primary);
            color: #000;
            font-weight: 600;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(255, 171, 29, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(255, 171, 29, 0.4);
        }

        .btn-outline-custom {
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            border-radius: 0.75rem;
            padding: 0.875rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: rgba(255, 171, 29, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .form-control, .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 171, 29, 0.15);
        }

        .input-group-text {
            color: var(--text-secondary);
        }

        .badge-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #000;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .text-primary-custom {
            color: var(--primary) !important;
        }

        .border-bottom-custom {
            border-bottom: 1px solid var(--border-subtle) !important;
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .alert {
            border-radius: 0.75rem;
            border: 1px solid;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .product-image {
            height: 140px;
            width: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .product-placeholder {
            height: 140px;
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.75rem 0.75rem 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .totals-section {
            background: rgba(255, 171, 29, 0.05);
            border: 1px solid rgba(255, 171, 29, 0.2);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .fly-item {
            position: fixed;
            z-index: 1060;
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            box-shadow: 0 0 10px rgba(255, 171, 29, 0.5);
        }

        /* Mobile Adjustments */
        @media (max-width: 991.98px) {
            .checkout-sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1050;
                background-color: var(--sidebar-bg);
            }

            .checkout-sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                height: 100dvh;
                padding-bottom: 300px;
            }
        }

        .mobile-cart-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            z-index: 1040;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            box-shadow: 0 8px 32px rgba(255, 171, 29, 0.4);
            color: #000;
            border: none;
            align-items: center;
            justify-content: space-between;
            animation: slideUp 0.3s ease-out;
            cursor: pointer;
        }

        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 991.98px) {
            .mobile-cart-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>

    <!-- Header Principal -->
    <header class="header-section">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1 fw-bold text-white">{{ $order->table->name ?? 'Mesa' }}</h4>
                    <span class="text-secondary" style="font-size: 0.9rem;">Orden #{{ $order->id }} • {{ $order->user->name ?? 'Usuario' }}</span>
                </div>
                
                <div class="d-flex gap-3">
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-custom d-flex align-items-center gap-2">
                        <i data-lucide="arrow-left" size="18"></i>
                        <span>Volver</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="container-fluid flex-grow-1 overflow-hidden">
        <div class="row h-100 g-0">
            
            <!-- Lado Izquierdo: Categorías y Productos -->
            <div class="col-lg-8 main-content">
                
                <!-- Buscador de Productos -->
                <div class="mb-4">
                    <div class="input-group" style="box-shadow: 0 4px 12px rgba(255, 171, 29, 0.15);">
                        <span class="input-group-text" style="background: var(--card-bg); border: 1px solid rgba(255, 171, 29, 0.3); border-right: none; color: var(--primary);">
                            <i data-lucide="search" size="20"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control fw-bold" 
                            style="background: var(--card-bg); border: 1px solid rgba(255, 171, 29, 0.3); border-left: none; color: var(--text-primary); font-size: 1rem;"
                            id="product-search" 
                            placeholder="Buscar productos..."
                            oninput="searchProducts()"
                        >
                    </div>
                </div>
                
                <!-- Categorías Rápidas -->
                <div class="d-flex gap-3 mb-4 overflow-auto pb-2 hide-scrollbar">
                    <div onclick="filterProducts('all')" class="card category-box category-active category-btn" id="cat-btn-all">
                        <i data-lucide="layout-grid" class="mb-2" size="24"></i>
                        <span class="fw-bold small">Todo</span>
                    </div>
                    @foreach($categories as $category)
                    <div onclick="filterProducts('{{ $category->id }}')" class="card category-box category-btn" id="cat-btn-{{ $category->id }}">
                        <i data-lucide="tag" class="mb-2" size="24"></i>
                        <span class="fw-bold small text-truncate w-100 px-1">{{ $category->name }}</span>
                    </div>
                    @endforeach
                </div>

                <!-- Rejilla de Productos -->
                <h5 class="mb-4 fw-bold text-white">Menú</h5>
                <div class="row g-4" id="products-grid">
                    @foreach($products as $product)
                    <div class="col-6 col-md-4 col-xl-3 product-item" data-category-id="{{ $product->category_id }}" data-product-name="{{ strtolower($product->name) }}">
                        <button type="button" 
                                onclick="openProductModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }})" 
                                class="card h-100 product-card w-100 text-start p-0 border-0">
                            @if($product->image)
                            <img src="{{ $product->image }}" class="product-image" alt="{{ $product->name }}">
                            @else
                            <div class="product-placeholder">
                                <i data-lucide="image" class="opacity-25" size="40"></i>
                            </div>
                            @endif
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2 text-truncate text-white">{{ $product->name }}</h6>
                                <p class="card-text fw-bold text-primary-custom mb-0" style="font-size: 1.1rem;">${{ number_format($product->price, 2) }}</p>
                            </div>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Lado Derecho: Checkout -->
            <div class="col-lg-4 checkout-sidebar p-0">
                <!-- Header de la Orden -->
                <div class="p-4 border-bottom-custom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-light d-lg-none p-2 rounded-circle border-0" onclick="toggleCart(false)">
                            <i data-lucide="arrow-left" size="24"></i>
                        </button>
                        <h5 class="mb-0 fw-bold text-white">Cuenta</h5>
                    </div>
                    <span class="badge-custom">{{ $order->details->count() }} ítems</span>
                </div>

                <!-- Lista de Ítems en la Orden -->
                <div class="order-list">
                    @foreach($order->details->reverse() as $detail)
                    <div class="order-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold text-white">{{ $detail->product->name ?? 'Producto' }}</h6>
                                
                                @if(in_array($detail->status, ['sent', 'preparing', 'ready']))
                                    <span class="badge mb-1" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.65rem; padding: 0.15rem 0.5rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                                        ✓ Cocina
                                    </span>
                                @else
                                    <span class="badge mb-1" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.65rem; padding: 0.15rem 0.5rem; border: 1px solid rgba(245, 158, 11, 0.3);">
                                        ⏳ Pendiente
                                    </span>
                                @endif
                                
                                @if($detail->notes)
                                <small class="text-secondary d-block mb-2"><i data-lucide="message-circle" size="12" class="me-1"></i>{{ $detail->notes }}</small>
                                @endif
                                <div class="text-secondary small">Cantidad: {{ $detail->quantity }}</div>
                            </div>
                            <div class="text-end ms-3">
                                <span class="fw-bold d-block text-white mb-2" style="font-size: 1.1rem;">${{ number_format($detail->price * $detail->quantity, 2) }}</span>
                                <form action="{{ route('orders.remove-item', [$order, $detail]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="from_checkout" value="1">
                                    <button type="submit" class="btn btn-link p-0 text-danger small text-decoration-none" style="font-size: 0.85rem;">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($order->details->isEmpty())
                    <div class="empty-state">
                        <i data-lucide="shopping-cart" size="48" class="mb-3 opacity-25"></i>
                        <p class="mb-0">La orden está vacía</p>
                        <small>Agrega productos del menú</small>
                    </div>
                    @endif
                </div>

                <!-- Resumen de Totales y Botón de Pago -->
                <div class="py-4 px-5 px-lg-4 border-top" style="border-top: 1px solid var(--border-subtle) !important; background: var(--sidebar-bg);">
                     <!-- Success/Error Messages -->
                     @if(session('success'))
                     <div class="alert alert-success py-2 px-3 mb-3">{{ session('success') }}</div>
                     @endif
                     @if(session('error'))
                     <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
                     @endif
                    
                    <div class="totals-section">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Subtotal</span>
                            <span class="fw-bold text-white">${{ number_format($order->total, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid rgba(255, 171, 29, 0.2);">
                            <span class="fw-bold text-white">Total</span>
                            <span class="fw-bold text-primary-custom" style="font-size: 1.75rem">${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>

                    @php
                        $pendingCount = $order->details->where('status', 'pending')->count();
                    @endphp

                    <div class="row g-3 mt-3">
                        @if($pendingCount > 0)
                        <div class="col-12">
                            <form action="{{ route('pos.send-to-kitchen', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-custom w-100 fw-bold d-flex flex-column align-items-center justify-content-center py-3 gap-2">
                                    <i data-lucide="send" size="20"></i>
                                    <span style="font-size: 0.9rem;">Enviar a Cocina ({{ $pendingCount }})</span>
                                </button>
                            </form>
                        </div>
                        @endif
                        <div class="col-6">
                            <a href="{{ route('orders.pre-check', $order) }}" target="_blank" class="btn btn-outline-custom w-100 fw-bold d-flex flex-column align-items-center justify-content-center py-3 gap-2">
                                <i data-lucide="printer" size="20"></i>
                                <span style="font-size: 0.9rem;">Imprimir</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-primary-custom w-100 fw-bold d-flex flex-column align-items-center justify-content-center py-3 gap-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i data-lucide="check-circle" size="20"></i>
                                <span style="font-size: 0.9rem;">Cobrar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Botón Flotante Móvil -->
    <button onclick="toggleCart(true)" class="mobile-cart-toggle">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-black bg-opacity-25 p-2 rounded-circle">
                <i data-lucide="shopping-cart" size="20" color="#000"></i>
            </div>
            <span class="fw-bold" style="font-size: 1.1rem">{{ $order->details->count() }} ítems</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small opacity-75">Total:</span>
            <span class="fw-black" style="font-size: 1.25rem">${{ number_format($order->total, 2) }}</span>
            <i data-lucide="chevron-right" size="20" class="ms-1"></i>
        </div>
    </button>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-subtle);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-subtle);">
                    <h5 class="modal-title text-white fw-bold" id="productModalLabel">
                        <i data-lucide="shopping-bag" size="20" class="me-2"></i>
                        <span id="modal-product-name">Producto</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Product Price -->
                    <div class="text-center mb-4">
                        <span class="text-secondary small">Precio unitario</span>
                        <h3 class="text-primary-custom fw-bold mb-0" id="modal-product-price">$0.00</h3>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-white mb-3">Cantidad</label>
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <button type="button" onclick="adjustModalQty(-1)" 
                                    class="btn btn-outline-custom rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 50px; height: 50px; font-size: 2rem; line-height: 1;">
                                −
                            </button>
                            <input type="number" id="modal-quantity" value="1" min="1" 
                                   class="form-control text-center fw-bold" 
                                   style="width: 100px; font-size: 2rem; background: var(--sidebar-bg); border: 1px solid var(--border-subtle); color: var(--text-primary);" 
                                   readonly>
                            <button type="button" onclick="adjustModalQty(1)" 
                                    class="btn btn-outline-custom rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 50px; height: 50px; font-size: 2rem; line-height: 1;">
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-white">Observaciones <span class="text-secondary fw-normal">(opcional)</span></label>
                        <textarea id="modal-notes" class="form-control" rows="3" 
                                  placeholder="Ej: Sin cebolla, término medio..."
                                  style="background: var(--sidebar-bg); border: 1px solid var(--border-subtle); color: var(--text-primary);"></textarea>
                    </div>

                    <!-- Total -->
                    <div class="p-3 rounded" style="background: var(--sidebar-bg); border: 1px solid var(--border-subtle);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white fw-bold">Total:</span>
                            <span class="text-primary-custom fw-bold" style="font-size: 1.5rem;" id="modal-total-price">$0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-subtle);">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" onclick="submitProduct()" class="btn btn-primary-custom d-flex align-items-center gap-2">
                        <i data-lucide="plus-circle" size="20"></i>
                        Agregar al Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-subtle);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-subtle);">
                    <h5 class="modal-title text-white fw-bold" id="paymentModalLabel">
                        <i data-lucide="credit-card" size="20" class="me-2"></i>
                        Procesar Pago
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="{{ route('pos.pay', $order) }}" method="POST" id="modal-payment-form">
                        @csrf
                        
                        <!-- Total Display -->
                        <div class="text-center mb-4 p-4" style="background: rgba(255, 171, 29, 0.1); border-radius: 1rem; border: 1px solid rgba(255, 171, 29, 0.3);">
                            <div class="text-secondary small mb-1">Total a Pagar</div>
                            <div class="text-primary-custom fw-bold" style="font-size: 2.5rem;">${{ number_format($order->total, 2) }}</div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-white mb-3">Método de Pago</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="modal-method-cash" value="cash" checked onchange="toggleModalReference(false)">
                                    <label class="payment-method-btn w-100 d-flex flex-column align-items-center justify-content-center py-3" for="modal-method-cash">
                                        <i data-lucide="banknote" size="28" class="mb-2"></i>
                                        <span class="fw-bold">Efectivo</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="modal-method-card" value="card" onchange="toggleModalReference(true)">
                                    <label class="payment-method-btn w-100 d-flex flex-column align-items-center justify-content-center py-3" for="modal-method-card">
                                        <i data-lucide="credit-card" size="28" class="mb-2"></i>
                                        <span class="fw-bold">Tarjeta</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="modal-method-transfer" value="transfer" onchange="toggleModalReference(true)">
                                    <label class="payment-method-btn w-100 d-flex flex-column align-items-center justify-content-center py-3" for="modal-method-transfer">
                                        <i data-lucide="landmark" size="28" class="mb-2"></i>
                                        <span class="fw-bold">Transfer.</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="modal-method-other" value="other" onchange="toggleModalReference(true)">
                                    <label class="payment-method-btn w-100 d-flex flex-column align-items-center justify-content-center py-3" for="modal-method-other">
                                        <i data-lucide="more-horizontal" size="28" class="mb-2"></i>
                                        <span class="fw-bold">Otro</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Payment Section -->
                        <div class="mb-4" id="modal-cash-input-group">
                            <label class="form-label fw-bold text-white">Monto Recibido</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control fw-bold" name="amount" id="modal-amount-input" value="{{ $order->total }}" oninput="calculateModalChange()">
                            </div>
                            <div class="d-flex justify-content-between mt-3 p-3" style="background: rgba(34, 197, 94, 0.1); border-radius: 0.75rem; border: 1px solid rgba(34, 197, 94, 0.2);">
                                <span class="text-secondary">Cambio:</span>
                                <span class="fw-bold" style="color: #4ade80; font-size: 1.5rem;" id="modal-change-display">$0.00</span>
                            </div>
                        </div>
                        
                        <!-- Reference Input (hidden by default) -->
                        <div class="mb-4 d-none" id="modal-reference-group">
                            <label class="form-label fw-bold text-white">Referencia</label>
                            <input type="text" class="form-control" name="reference" placeholder="Número de referencia (opcional)">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary-custom w-100 py-3 d-flex justify-content-center align-items-center gap-2" style="font-size: 1.1rem;">
                            <i data-lucide="check-circle" size="24"></i>
                            COBRAR Y CERRAR
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar iconos de Lucide
        lucide.createIcons();

        function toggleCart(show) {
            const sidebar = document.querySelector('.checkout-sidebar');
            if(show) {
                sidebar.classList.add('active');
            } else {
                sidebar.classList.remove('active');
            }
        }
        
        const total = {{ $order->total }};
        const modalAmountInput = document.getElementById('modal-amount-input');
        const modalChangeDisplay = document.getElementById('modal-change-display');
        
        function calculateModalChange() {
            const amountReceived = parseFloat(modalAmountInput.value) || 0;
            const change = amountReceived - total;
            modalChangeDisplay.textContent = '$' + change.toFixed(2);
        }
        
        function filterProducts(categoryId) {
            // Reset active styles
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('category-active');
            });

            // Set active style
            const activeBtn = document.getElementById('cat-btn-' + categoryId);
            if(activeBtn) {
                activeBtn.classList.add('category-active');
            }

            // Filter Grid
            const items = document.querySelectorAll('.product-item');
            items.forEach(item => {
                if (categoryId === 'all' || item.dataset.categoryId == categoryId) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function searchProducts() {
            const searchTerm = document.getElementById('product-search').value.toLowerCase();
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const productName = item.dataset.productName;
                if (productName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });

            // If searching, reset category filter visual state
            if (searchTerm !== '') {
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('category-active');
                });
            }
        }
        
        function toggleModalReference(show) {
            const refGroup = document.getElementById('modal-reference-group');
            const cashGroup = document.getElementById('modal-cash-input-group');
            
            if (show) {
                refGroup.classList.remove('d-none');
                cashGroup.classList.add('d-none');
                // Auto-set exact amount for non-cash
                modalAmountInput.value = total.toFixed(2);
            } else {
                refGroup.classList.add('d-none');
                cashGroup.classList.remove('d-none');
            }
            calculateModalChange();
        }
        
        // Init modal change calculation
        calculateModalChange();

        // === Product Modal Functions ===
        let selectedProduct = null;
        
        function openProductModal(productId, productName, productPrice) {
            selectedProduct = {
                id: productId,
                name: productName,
                price: productPrice
            };
            
            document.getElementById('modal-product-name').textContent = productName;
            document.getElementById('modal-product-price').textContent = '$' + productPrice.toFixed(2);
            document.getElementById('modal-quantity').value = 1;
            document.getElementById('modal-notes').value = '';
            updateModalTotal();
            
            const modalEl = document.getElementById('productModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            
            // Refresh icons when modal is shown
            modalEl.addEventListener('shown.bs.modal', function () {
                lucide.createIcons();
            }, { once: true });
        }
        
        function adjustModalQty(delta) {
            const input = document.getElementById('modal-quantity');
            const newVal = Math.max(1, parseInt(input.value) + delta);
            input.value = newVal;
            updateModalTotal();
            lucide.createIcons(); // Refresh icons after button click
        }
        
        function updateModalTotal() {
            if (!selectedProduct) return;
            const qty = parseInt(document.getElementById('modal-quantity').value);
            const total = selectedProduct.price * qty;
            document.getElementById('modal-total-price').textContent = '$' + total.toFixed(2);
        }
        
        function submitProduct() {
            if (!selectedProduct) return;

            // --- Animation Logic ---
            const btn = document.querySelector('#productModal .btn-primary-custom');
            // Disable button to prevent double clicks
            if(btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';
            }

            const mobileCart = document.querySelector('.mobile-cart-toggle');
            const desktopCart = document.querySelector('.checkout-sidebar .badge-custom');
            
            // Determine target (mobile toggle if visible, else desktop badge)
            let target = null;
            if (mobileCart && window.getComputedStyle(mobileCart).display !== 'none') {
                target = mobileCart;
            } else {
                target = desktopCart;
            }

            if (btn && target) {
                const startRect = btn.getBoundingClientRect();
                const endRect = target.getBoundingClientRect();
                
                const flyer = document.createElement('div');
                flyer.classList.add('fly-item');
                
                // Start center of button
                flyer.style.left = (startRect.left + startRect.width/2 - 10) + 'px';
                flyer.style.top = (startRect.top + startRect.height/2 - 10) + 'px';
                
                document.body.appendChild(flyer);

                // Force reflow
                void flyer.offsetWidth;

                // Animate to center of target
                flyer.style.left = (endRect.left + endRect.width/2 - 10) + 'px';
                flyer.style.top = (endRect.top + endRect.height/2 - 10) + 'px';
                flyer.style.opacity = '0.5';
                flyer.style.transform = 'scale(0.5)';

                // Visual feedback on target
                setTimeout(() => {
                    target.style.transform = 'scale(1.2)';
                    setTimeout(() => target.style.transform = '', 200);
                    flyer.remove();
                }, 500);
            }
            // -----------------------
            
            setTimeout(() => {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("orders.add-item", $order) }}';
                
                // CSRF Token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                
                // Product ID
                const productId = document.createElement('input');
                productId.type = 'hidden';
                productId.name = 'product_id';
                productId.value = selectedProduct.id;
                form.appendChild(productId);
                
                // Quantity
                const quantity = document.createElement('input');
                quantity.type = 'hidden';
                quantity.name = 'quantity';
                quantity.value = document.getElementById('modal-quantity').value;
                form.appendChild(quantity);
                
                // Notes
                const notes = document.createElement('input');
                notes.type = 'hidden';
                notes.name = 'notes';
                notes.value = document.getElementById('modal-notes').value;
                form.appendChild(notes);
                
                // From Checkout
                const fromCheckout = document.createElement('input');
                fromCheckout.type = 'hidden';
                fromCheckout.name = 'from_checkout';
                fromCheckout.value = '1';
                form.appendChild(fromCheckout);
                
                // Append to body and submit
                document.body.appendChild(form);
                form.submit();
            }, 450); // Slight delay to let animation start
        }

        // Refresh icons when modal is shown
        const paymentModal = document.getElementById('paymentModal');
        if (paymentModal) {
            paymentModal.addEventListener('shown.bs.modal', function () {
                lucide.createIcons();
                calculateModalChange();
            });
        }
    </script>
</body>
</html>
