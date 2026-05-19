@extends('layouts.pos')
@section('title', 'Comanda Mesa ' . $order->table->name)
@section('content')
<div class="row h-100">
    <!-- Product Catalog (Left) -->
    <div class="col-md-8 h-100 d-flex flex-column">
        <div class="card mb-3">
            <div class="card-body py-2">
                <ul class="nav nav-pills" id="categoryTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cat-all" type="button">Todos</button>
                    </li>
                    @foreach($categories as $category)
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cat-{{ $category->id }}" type="button">{{ $category->name }}</button>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="tab-content flex-grow-1 overflow-auto" style="height: calc(100vh - 200px);">
            <!-- All Products Tab -->
            <div class="tab-pane fade show active" id="cat-all">
                <div class="row g-3">
                    @foreach($products as $product)
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card h-100 cursor-pointer" onclick="addProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
                            <div class="card-body text-center p-3">
                                <div class="avatar avatar-xl mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">{{ substr($product->name, 0, 2) }}</span>
                                </div>
                                <h6 class="mb-1 text-truncate">{{ $product->name }}</h6>
                                <p class="mb-0 text-primary fw-bold">${{ number_format($product->price, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Category Tabs -->
            @foreach($categories as $category)
            <div class="tab-pane fade" id="cat-{{ $category->id }}">
                <div class="row g-3">
                    @foreach($products->where('category_id', $category->id) as $product)
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card h-100 cursor-pointer" onclick="addProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
                            <div class="card-body text-center p-3">
                                <div class="avatar avatar-xl mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">{{ substr($product->name, 0, 2) }}</span>
                                </div>
                                <h6 class="mb-1 text-truncate">{{ $product->name }}</h6>
                                <p class="mb-0 text-primary fw-bold">${{ number_format($product->price, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Order Summary (Right) -->
    <div class="col-md-4 h-100">
        <div class="card h-100 d-flex flex-column" style="height: calc(100vh - 120px) !important;">
            <div class="card-header border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mesa {{ $order->table->name }}</h5>
                    <span class="badge bg-label-primary">#{{ $order->id }}</span>
                </div>
                <small class="text-muted">{{ $order->user->name ?? 'Usuario' }} | {{ $order->created_at->format('H:i') }}</small>
            </div>
            
            <div class="card-body flex-grow-1 overflow-auto p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Cant.</th>
                            <th>Producto</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->details->where('is_combo_component', false) as $detail)
                        <tr>
                            <td>{{ $detail->quantity }}</td>
                            <td>
                                <div class="fw-bold">{{ $detail->product_name }}</div>
                                @if($detail->flavor_name)
                                <small class="text-info d-block">Sabor: {{ $detail->flavor_name }}</small>
                                @endif
                                <small class="text-muted">{{ $detail->notes }}</small>
                            </td>
                            <td class="text-end">${{ number_format($detail->price * $detail->quantity, 2) }}</td>
                            <td class="text-end">
                                @if($order->status == 'open')
                                <form action="{{ route('orders.remove-item', [$order, $detail]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon text-danger"><i class="ti tabler-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer border-top bg-light">
                <div class="d-flex justify-content-between mb-3">
                    <span class="h5 mb-0">Total:</span>
                    <span class="h4 mb-0 text-primary">${{ number_format($order->total, 2) }}</span>
                </div>
                <div class="d-grid gap-2">
                    @if($order->status == 'open')
                    <!-- <button class="btn btn-warning">Enviar a Cocina</button> -->
                    <a href="{{ route('orders.pre-check', $order) }}" target="_blank" class="btn btn-outline-dark w-100 mb-2">
                        <i class="ti tabler-printer me-1"></i> Imprimir Cuenta
                    </a>
                    @if(!auth()->user()->hasRole('mesero'))
                    <a href="{{ route('pos.checkout', $order) }}" class="btn btn-success w-100">
                        <i class="ti tabler-cash me-1"></i> Cobrar
                    </a>
                    @endif
                    @else
                    <button class="btn btn-secondary" disabled>Cerrada</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Multiply Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addProductForm" action="{{ route('orders.add-item', $order) }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="product_id" id="input_product_id">
            <input type="hidden" name="product_flavor_id" id="input_product_flavor_id">
            <input type="hidden" name="combo_components" id="input_combo_components">
            
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductTitle">Agregar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Sabor</label>
                        <select class="form-select" id="input_flavor_select" onchange="syncFlavorValue()">
                            <option value="">Sin sabor</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3 d-none" id="combo-components-picker">
                        <label class="form-label">Componentes del combo</label>
                        <div id="combo-components-list"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Cantidad</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(-1)">-</button>
                            <input type="number" name="quantity" id="input_quantity" class="form-control text-center" value="1" min="1" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(1)">+</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Sin cebolla, muy cocido, etc..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agregar</button>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script>
const productFlavorsMap = @json($productFlavorsMap ?? []);
const productCombosMap = @json($productCombosMap ?? []);

function addProduct(id, name) {
    document.getElementById('input_product_id').value = id;
    document.getElementById('modalProductTitle').innerText = name;
    document.getElementById('input_quantity').value = 1;
    document.querySelector('textarea[name="notes"]').value = '';
    loadFlavorOptions(id);
    loadComboComponents(id);
    
    var myModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    myModal.show();
    
    // Focus quantity input after modal opens
    setTimeout(() => {
        document.getElementById('input_quantity').select();
    }, 500);
}

function adjustQty(amount) {
    let input = document.getElementById('input_quantity');
    let current = parseInt(input.value) || 0;
    let newValue = current + amount;
    if(newValue < 1) newValue = 1;
    input.value = newValue;
}

function loadFlavorOptions(productId) {
    const select = document.getElementById('input_flavor_select');
    const hidden = document.getElementById('input_product_flavor_id');
    const flavors = productFlavorsMap[productId] || [];
    select.innerHTML = '<option value=\"\">Sin sabor</option>';
    flavors.forEach(flavor => {
        const extra = Number(flavor.additional_price || 0);
        const label = extra > 0 ? `${flavor.name} (+$${extra.toFixed(2)})` : flavor.name;
        select.insertAdjacentHTML('beforeend', `<option value=\"${flavor.id}\">${label}</option>`);
    });
    select.closest('.col-12').style.display = flavors.length ? 'block' : 'none';
    hidden.value = '';
}

function syncFlavorValue() {
    document.getElementById('input_product_flavor_id').value = document.getElementById('input_flavor_select').value;
}

function loadComboComponents(productId) {
    const wrapper = document.getElementById('combo-components-picker');
    const list = document.getElementById('combo-components-list');
    const components = productCombosMap[productId] || [];
    if (!components.length) {
        wrapper.classList.add('d-none');
        list.innerHTML = '';
        document.getElementById('input_combo_components').value = '';
        return;
    }

    wrapper.classList.remove('d-none');
    list.innerHTML = components.map((c, i) => {
        const options = ['<option value="">Sin sabor</option>'].concat((c.flavors || []).map(f => `<option value="${f.id}" ${String(c.default_flavor_id)===String(f.id)?'selected':''}>${f.name}</option>`)).join('');
        return `<div class="row g-2 mb-2 combo-component-row" data-component-id="${c.component_product_id}">
            <div class="col-6"><input class="form-control" readonly value="${c.component_name} x${c.quantity}"></div>
            <div class="col-6"><select class="form-select combo-component-flavor">${options}</select></div>
        </div>`;
    }).join('');
    syncComboComponentsValue();
    list.querySelectorAll('.combo-component-flavor').forEach(sel => sel.addEventListener('change', syncComboComponentsValue));
}

function syncComboComponentsValue() {
    const rows = document.querySelectorAll('#combo-components-list .combo-component-row');
    const payload = Array.from(rows).map(row => ({
        component_product_id: Number(row.dataset.componentId),
        product_flavor_id: row.querySelector('.combo-component-flavor').value || null
    }));
    document.getElementById('input_combo_components').value = JSON.stringify(payload);
}
</script>
@endsection

@endsection
