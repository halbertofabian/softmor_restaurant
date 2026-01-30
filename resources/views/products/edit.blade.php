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
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">Descripción</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $product->description }}</textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="category_id">Categoría</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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

            <div class="mb-3">
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

@section('scripts')
<script>
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
</script>
@endsection
@endsection
