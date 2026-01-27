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
        body {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .main-content {
            flex: 1;
            overflow-y: auto;
        }
        .checkout-sidebar {
            border-left: 1px solid rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .product-card {
            cursor: pointer;
            transition: transform 0.1s;
        }
        .product-card:active {
            transform: scale(0.98);
        }
        .order-list {
            flex: 1;
            overflow-y: auto;
        }
        .category-box {
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .category-box:hover {
            transform: translateY(-2px);
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .category-active {
            background-color: #0d6efd !important;
            color: white !important;
            border-color: #0d6efd !important;
        }
    </style>
</head>
<body class="bg-body-tertiary">

    <!-- Header Principal -->
    <header class="p-3 bg-body border-bottom">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0 fw-bold">{{ $order->table->name ?? 'Mesa' }}</h4>
                    <span class="text-muted small">Orden #{{ $order->id }} - {{ $order->user->name ?? 'Usuario' }}</span>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i data-lucide="arrow-left" size="18"></i>
                        <span>Volver</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="container-fluid flex-grow-1 overflow-hidden">
        <div class="row h-100">
            
            <!-- Lado Izquierdo: Categorías y Productos -->
            <div class="col-lg-8 main-content p-4">
                
                <!-- Categorías Rápidas -->
                <div class="d-flex gap-3 mb-4 overflow-auto pb-2 hide-scrollbar">
                    <div onclick="filterProducts('all')" class="card category-box bg-dark text-white border-0 shadow-sm category-btn" id="cat-btn-all" style="min-width: 100px;">
                        <i data-lucide="layout-grid" class="mb-2"></i>
                        <span class="fw-bold small">Todo</span>
                    </div>
                    @foreach($categories as $category)
                    <div onclick="filterProducts('{{ $category->id }}')" class="card category-box bg-white text-dark border shadow-sm category-btn" id="cat-btn-{{ $category->id }}" style="min-width: 100px;">
                        <i data-lucide="tag" class="mb-2"></i>
                        <span class="fw-bold small text-truncate w-100 px-1">{{ $category->name }}</span>
                    </div>
                    @endforeach
                </div>

                <!-- Rejilla de Productos -->
                <h5 class="mb-3 fw-bold">Menú</h5>
                <div class="row g-3" id="products-grid">
                    @foreach($products as $product)
                    <div class="col-6 col-md-4 col-xl-3 product-item" data-category-id="{{ $product->category_id }}">
                        <form action="{{ route('orders.add-item', $order) }}" method="POST" class="h-100">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="from_checkout" value="1">
                            
                            <button type="submit" class="card h-100 border-0 shadow-sm product-card w-100 text-start p-0 bg-white">
                                @if($product->image)
                                <img src="{{ $product->image }}" class="card-img-top" alt="{{ $product->name }}" style="height: 120px; object-fit: cover;">
                                @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                    <i data-lucide="image" class="opacity-25" size="40"></i>
                                </div>
                                @endif
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1 text-truncate">{{ $product->name }}</h6>
                                    <p class="card-text fw-bold text-primary mb-0">${{ number_format($product->price, 2) }}</p>
                                </div>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Lado Derecho: Checkout -->
            <div class="col-lg-4 bg-body checkout-sidebar p-0">
                <!-- Header de la Orden -->
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Cuenta</h5>
                    <span class="badge bg-primary rounded-pill">{{ $order->details->count() }} ítems</span>
                </div>

                <!-- Lista de Ítems en la Orden -->
                <div class="order-list p-3">
                    <div class="list-group list-group-flush">
                        @foreach($order->details as $detail)
                        <div class="list-group-item px-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $detail->product->name ?? 'Producto' }}</h6>
                                    @if($detail->notes)
                                    <small class="text-muted">{{ $detail->notes }}</small>
                                    @endif
                                    <div class="text-muted small mt-1">x{{ $detail->quantity }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold d-block">${{ number_format($detail->price * $detail->quantity, 2) }}</span>
                                    <form action="{{ route('orders.remove-item', [$order, $detail]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="from_checkout" value="1">
                                        <button type="submit" class="btn btn-link p-0 text-danger small text-decoration-none" style="font-size: 0.75rem;">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        @if($order->details->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i data-lucide="shopping-cart" size="40" class="mb-3 opacity-25"></i>
                            <p>La orden está vacía</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Resumen de Totales y Botón de Pago -->
                <div class="p-3 border-top bg-body-tertiary">
                     <!-- Success/Error Messages -->
                     @if(session('success'))
                     <div class="alert alert-success py-1 px-2 small mb-2">{{ session('success') }}</div>
                     @endif
                     @if(session('error'))
                     <div class="alert alert-danger py-1 px-2 small mb-2">{{ session('error') }}</div>
                     @endif
                    
                    <a href="{{ route('orders.pre-check', $order) }}" target="_blank" class="btn btn-outline-dark w-100 mb-3 fw-bold d-flex justify-content-center align-items-center gap-2">
                        <i data-lucide="printer" size="18"></i>
                        IMPRIMIR CUENTA
                    </a>

                    <form action="{{ route('pos.pay', $order) }}" method="POST" id="payment-form">
                        @csrf
                        
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold">${{ number_format($order->total, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Total</span>
                            <span class="fw-bold fs-4 text-primary">${{ number_format($order->total, 2) }}</span>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="mb-3">
                             <label class="form-label small fw-bold text-muted text-uppercase">Método de Pago</label>
                             <div class="row g-2">
                                 <div class="col-3">
                                     <input type="radio" class="btn-check" name="method" id="method-cash" value="cash" checked onchange="toggleReference(false)">
                                     <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center justify-content-center p-2" for="method-cash">
                                         <i data-lucide="banknote" size="18" class="mb-1"></i>
                                         <span style="font-size: 0.7rem;">Efectivo</span>
                                     </label>
                                 </div>
                                 <div class="col-3">
                                     <input type="radio" class="btn-check" name="method" id="method-card" value="card" onchange="toggleReference(true)">
                                     <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center justify-content-center p-2" for="method-card">
                                         <i data-lucide="credit-card" size="18" class="mb-1"></i>
                                         <span style="font-size: 0.7rem;">Tarjeta</span>
                                     </label>
                                 </div>
                                 <div class="col-3">
                                     <input type="radio" class="btn-check" name="method" id="method-transfer" value="transfer" onchange="toggleReference(true)">
                                     <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center justify-content-center p-2" for="method-transfer">
                                         <i data-lucide="landmark" size="18" class="mb-1"></i>
                                         <span style="font-size: 0.7rem;">Transf.</span>
                                     </label>
                                 </div>
                                 <div class="col-3">
                                     <input type="radio" class="btn-check" name="method" id="method-other" value="other" onchange="toggleReference(true)">
                                     <label class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center justify-content-center p-2" for="method-other">
                                         <i data-lucide="more-horizontal" size="18" class="mb-1"></i>
                                         <span style="font-size: 0.7rem;">Otro</span>
                                     </label>
                                 </div>
                             </div>
                        </div>

                         <div class="mb-3" id="cash-input-group">
                            <label class="form-label small fw-bold text-muted">Monto Recibido</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control fw-bold" name="amount" id="amount-input" value="{{ $order->total }}" oninput="calculateChange()">
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="small text-muted">Cambio:</span>
                                <span class="fw-bold text-success" id="change-display">$0.00</span>
                            </div>
                         </div>
                         
                         <div class="mb-3 d-none" id="reference-group">
                             <label class="form-label small fw-bold text-muted">Referencia</label>
                             <input type="text" class="form-control" name="reference" placeholder="Opcional">
                         </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2">
                            <i data-lucide="check-circle" size="20"></i>
                            COBRAR Y CERRAR
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar iconos de Lucide
        lucide.createIcons();
        
        const total = {{ $order->total }};
        const amountInput = document.getElementById('amount-input');
        const changeDisplay = document.getElementById('change-display');
        
        function filterProducts(categoryId) {
            // Reset active styles
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.add('bg-white', 'text-dark');
                btn.classList.remove('bg-dark', 'text-white', 'bg-primary');
            });

            // Set active style
            const activeBtn = document.getElementById('cat-btn-' + categoryId);
            if(activeBtn) {
                activeBtn.classList.remove('bg-white', 'text-dark');
                activeBtn.classList.add('bg-dark', 'text-white');
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

        function toggleReference(show) {
            const refGroup = document.getElementById('reference-group');
            const cashGroup = document.getElementById('cash-input-group');
            
            if (show) {
                refGroup.classList.remove('d-none');
                // Auto-set exact amount for card
                amountPaidInput.value = total.toFixed(2);
                amountPaidInput.readOnly = true;
            } else {
                refGroup.classList.add('d-none');
                amountPaidInput.readOnly = false;
            }
            calculateChange();
        }
        
        // Init
        calculateChange();
        // Set All active by default
        // filterProducts('all'); // Already set by initial class state but good to enforce if needed.
    </script>
</body>
</html>
