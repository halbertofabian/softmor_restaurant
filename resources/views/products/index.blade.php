@extends('layouts.master')
@section('title', 'Productos')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos</h5>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i> Nuevo Producto
        </a>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table" id="products-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Sabores</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="product-details-name">Detalle del producto</h5>
                    <span class="badge bg-label-primary mt-1" id="product-details-type"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="card border shadow-none mb-3">
                    <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-info-circle me-2"></i>Información general</h6></div>
                    <div class="card-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Categoría</small><span id="product-details-category"></span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Área de preparación</small><span id="product-details-area"></span></div>
                            <div class="col-md-2"><small class="text-muted d-block">Precio</small><span id="product-details-price"></span></div>
                            <div class="col-md-2"><small class="text-muted d-block">Estado</small><span id="product-details-status"></span></div>
                            <div class="col-12"><small class="text-muted d-block">Descripción</small><span id="product-details-description"></span></div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-none mb-3 d-none" id="product-details-inventory-card">
                    <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-package me-2"></i>Inventario de producto terminado</h6></div>
                    <div class="card-body pt-3"><div class="row"><div class="col-6"><small class="text-muted d-block">Stock actual</small><span id="product-details-stock"></span></div><div class="col-6"><small class="text-muted d-block">Stock mínimo</small><span id="product-details-min-stock"></span></div></div></div>
                </div>

                <div class="card border shadow-none mb-3 d-none" id="product-details-flavors-card">
                    <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-adjustments me-2"></i>Sabores</h6></div>
                    <div class="card-body pt-3"><div class="d-flex flex-wrap gap-2" id="product-details-flavors"></div></div>
                </div>

                <div class="card border shadow-none mb-3 d-none" id="product-details-recipe-card">
                    <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-receipt me-2"></i>Receta de materias primas</h6></div>
                    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Materia prima</th><th>Cantidad por unidad</th></tr></thead><tbody id="product-details-recipe"></tbody></table></div>
                </div>

                <div class="card border shadow-none d-none" id="product-details-combo-card">
                    <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-box me-2"></i>Componentes del combo</h6></div>
                    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Producto</th><th>Cantidad</th></tr></thead><tbody id="product-details-combo"></tbody></table></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@push('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.min.js') }}"></script>
<script>
    const productDetailsModal = document.getElementById('productDetailsModal');

    productDetailsModal.addEventListener('show.bs.modal', function (event) {
        const product = JSON.parse(event.relatedTarget.dataset.product);
        document.getElementById('product-details-name').textContent = product.name;
        document.getElementById('product-details-type').textContent = product.type;
        document.getElementById('product-details-category').textContent = product.category;
        document.getElementById('product-details-area').textContent = product.preparation_area;
        document.getElementById('product-details-price').textContent = `$${product.price}`;
        document.getElementById('product-details-status').textContent = product.status;
        document.getElementById('product-details-description').textContent = product.description;

        const inventoryCard = document.getElementById('product-details-inventory-card');
        inventoryCard.classList.toggle('d-none', !product.controls_inventory);
        document.getElementById('product-details-stock').textContent = product.stock ?? 0;
        document.getElementById('product-details-min-stock').textContent = product.min_stock ?? 'No definido';

        const flavorsCard = document.getElementById('product-details-flavors-card');
        flavorsCard.classList.toggle('d-none', product.flavors.length === 0);
        const flavors = document.getElementById('product-details-flavors');
        flavors.replaceChildren(...product.flavors.map(flavor => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-label-secondary';
            badge.textContent = `${flavor.name} (+$${flavor.additional_price})`;
            return badge;
        }));

        const recipeCard = document.getElementById('product-details-recipe-card');
        recipeCard.classList.toggle('d-none', product.recipe.length === 0);
        const recipe = document.getElementById('product-details-recipe');
        recipe.replaceChildren(...product.recipe.map(item => {
            const row = document.createElement('tr');
            const name = document.createElement('td');
            const quantity = document.createElement('td');
            name.textContent = item.name;
            quantity.textContent = `${item.quantity} ${item.unit}`;
            row.append(name, quantity);
            return row;
        }));

        const comboCard = document.getElementById('product-details-combo-card');
        comboCard.classList.toggle('d-none', product.combo_items.length === 0);
        const combo = document.getElementById('product-details-combo');
        combo.replaceChildren(...product.combo_items.map(item => {
            const row = document.createElement('tr');
            const name = document.createElement('td');
            const quantity = document.createElement('td');
            name.textContent = item.name;
            quantity.textContent = item.quantity;
            row.append(name, quantity);
            return row;
        }));
    });

    GF.createAjaxDataTable('#products-table', {
        ajax: "{{ route('products.datatable') }}",
        responsive: true,
        columns: [
            { data: 'name' },
            { data: 'type', orderable: false, searchable: false },
            { data: 'category' },
            { data: 'price' },
            { data: 'flavors', orderable: false, searchable: false },
            { data: 'stock', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [1, 4, 5, 6, 7], render: function (data) { return data; } }
        ]
    });
</script>
@endpush
