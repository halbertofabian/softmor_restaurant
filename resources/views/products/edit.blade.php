@extends('layouts.master')
@section('title', 'Editar Producto')
@section('content')
<div class="card mb-4">
    <h5 class="card-header">Editar Producto</h5>
    <div class="card-body">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="name">Nombre</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $product->name }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="type">Tipo</label>
                    <select class="form-select" id="type" name="type" required onchange="toggleInventory()">
                        <option value="dish" {{ $product->type == 'dish' ? 'selected' : '' }}>Platillo</option>
                        <option value="drink" {{ $product->type == 'drink' ? 'selected' : '' }}>Bebida</option>
                        <option value="finished" {{ $product->type == 'finished' ? 'selected' : '' }}>Producto Terminado</option>
                        <option value="extra" {{ $product->type == 'extra' ? 'selected' : '' }}>Extra</option>
                        <option value="combo" {{ $product->type == 'combo' ? 'selected' : '' }}>Combo</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">Descripción</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $product->description }}</textarea>
            </div>
            
            <div class="row" id="classification-wrapper">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="category_id">Categoría</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-preparation-area-id="{{ $category->preparation_area_id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="preparation_area_id">Área de Preparación</label>
                    <select class="form-select" id="preparation_area_id" name="preparation_area_id" required>
                        @foreach($preparationAreas as $area)
                            <option value="{{ $area->id }}" {{ $product->preparation_area_id == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="price">Precio</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="price" name="price" value="{{ $product->price }}" step="0.01" min="0" required>
                </div>
            </div>

            <div class="mb-3 {{ $product->type === 'combo' ? 'd-none' : '' }}" id="flavors-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Sabores (opcional)</label>
                    <button type="button" class="btn btn-sm btn-label-primary" onclick="addFlavorRow()">Agregar sabor</button>
                </div>
                <div id="flavors-container"></div>
            </div>

            <div class="mb-3 {{ $product->type === 'combo' ? '' : 'd-none' }}" id="combo-components-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Componentes del combo (sin duplicar variantes)</label>
                    <button type="button" class="btn btn-sm btn-label-primary" onclick="addComboComponentRow()">Agregar componente</button>
                </div>
                <div id="combo-components-container"></div>
            </div>

            <div class="mb-3 {{ $product->type === 'combo' ? 'd-none' : '' }}" id="inventory-wrapper">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="controls_inventory" name="controls_inventory" {{ $product->controls_inventory ? 'checked' : '' }} onchange="toggleStockFields()">
                    <label class="form-check-label" for="controls_inventory">Controlar Inventario</label>
                </div>
            </div>

            <div class="row {{ $product->controls_inventory ? '' : 'd-none' }}" id="stock-fields">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="stock">Stock Actual</label>
                    <input type="number" class="form-control" id="stock" name="stock" value="{{ $product->stock }}">
                    <div class="form-text">Si modificas este valor, se generará un ajuste de inventario.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="min_stock">Stock Mínimo</label>
                    <input type="number" class="form-control" id="min_stock" name="min_stock" value="{{ $product->min_stock }}">
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" {{ $product->status ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Activo</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('products.index') }}" class="btn btn-label-secondary">Cancelar</a>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function addFlavorRow(name = '', price = 0) {
    const container = document.getElementById('flavors-container');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 flavor-row';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="text" class="form-control" name="flavor_name[]" placeholder="Nombre del sabor" value="${name}">
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" class="form-control" name="flavor_price[]" value="${price}">
            </div>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-label-danger w-100" onclick="this.closest('.flavor-row').remove()">X</button>
        </div>
    `;
    container.appendChild(row);
}

function toggleInventory() {
    const type = document.getElementById('type').value;
    const inventoryCheck = document.getElementById('controls_inventory');
    
    // Auto-check for finished products if changing to finished
    // But in edit, we should careful not to override user choice if they customized it.
    // For now I'll keep duplicate logic but maybe relaxed? 
    // The user requirement says: "Product type should not change freely if it has sales". 
    // I haven't implemented that check yet because I don't have sales. 
    // For MVP toggle logic:
    if (type === 'finished') {
        inventoryCheck.checked = true;
    } 
    // Don't auto-uncheck in Edit mode just in case they want a Drink to track inventory?
    // Let's stick to the same logic for consistency.
    
    toggleStockFields();
    toggleComboFields();
}

function toggleStockFields() {
    const isChecked = document.getElementById('controls_inventory').checked;
    const stockFields = document.getElementById('stock-fields');
    
    if (isChecked) {
        stockFields.classList.remove('d-none');
    } else {
        stockFields.classList.add('d-none');
    }
}

const existingFlavors = @json($existingFlavorsData ?? []);

if (existingFlavors.length) {
    existingFlavors.forEach(flavor => addFlavorRow(flavor.name, flavor.additional_price));
} else {
    addFlavorRow();
}

const comboProductsData = @json($comboProductsData ?? []);

const existingComboItems = @json($existingComboItemsData ?? []);

function toggleComboFields() {
    const type = document.getElementById('type').value;
    document.getElementById('combo-components-wrapper').classList.toggle('d-none', type !== 'combo');
    document.getElementById('flavors-wrapper').classList.toggle('d-none', type === 'combo');
    document.getElementById('inventory-wrapper').classList.toggle('d-none', type === 'combo');
    document.getElementById('classification-wrapper').classList.toggle('d-none', type === 'combo');
    if (type === 'combo') {
        document.getElementById('controls_inventory').checked = false;
        toggleStockFields();
    }
}

function productOptions(selected = '') {
    return comboProductsData.map(p => `<option value="${p.id}" ${String(selected) === String(p.id) ? 'selected' : ''}>${p.name}</option>`).join('');
}

function addComboComponentRow(productId = '', qty = 1) {
    const container = document.getElementById('combo-components-container');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 combo-row';
    row.innerHTML = `
        <div class="col-md-7">
            <select class="form-select" name="combo_component_product_id[]" onchange="refreshRowFlavors(this)">
                <option value="">Producto</option>
                ${productOptions(productId)}
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" min="1" class="form-control" name="combo_component_quantity[]" value="${qty}" placeholder="Cant.">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-label-danger w-100" onclick="this.closest('.combo-row').remove()">X</button>
        </div>
    `;
    container.appendChild(row);
}

function refreshRowFlavors(selectEl) {
    return;
}

if (existingComboItems.length) {
    existingComboItems.forEach(item => addComboComponentRow(item.component_product_id, item.quantity));
}

function autoSelectAreaFromCategory() {
    const categorySelect = document.getElementById('category_id');
    const areaSelect = document.getElementById('preparation_area_id');
    if (!categorySelect || !areaSelect) return;

    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const defaultAreaId = selectedOption?.dataset?.preparationAreaId;
    if (!defaultAreaId) return;

    const hasAreaOption = Array.from(areaSelect.options).some(o => String(o.value) === String(defaultAreaId));
    if (hasAreaOption) {
        areaSelect.value = defaultAreaId;
    }
}

document.getElementById('category_id')?.addEventListener('change', autoSelectAreaFromCategory);

toggleComboFields();
</script>
@endpush
