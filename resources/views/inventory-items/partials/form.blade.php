@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input class="form-control" name="name" value="{{ old('name', $item?->name) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Unidad base</label><select class="form-select" id="base_unit" name="base_unit" required>
        @foreach(['g' => 'Gramos', 'ml' => 'Mililitros', 'unit' => 'Unidades'] as $value => $label)<option value="{{ $value }}" @selected(old('base_unit', $item?->base_unit) === $value)>{{ $label }}</option>@endforeach
    </select></div>
    @if(!$item)<div class="col-md-3 mb-3"><label class="form-label">Existencia inicial</label><div class="input-group"><input type="number" step="0.001" class="form-control" name="stock" value="{{ old('stock', 0) }}" required><select class="form-select" id="stock_unit" name="stock_unit" required>@foreach(['g','kg','ml','l','unit'] as $unit)<option value="{{ $unit }}" @selected(old('stock_unit', 'g') === $unit)>{{ $unit }}</option>@endforeach</select></div></div>
    @else<div class="col-md-3 mb-3"><label class="form-label">Existencia actual</label><input class="form-control" value="{{ number_format((float) $item->stock, 3, '.', '') }} {{ $item->base_unit }}" disabled><div class="form-text">Los cambios de existencia se harán desde Ajustes.</div></div>@endif
    <div class="col-md-3 mb-3"><label class="form-label">Existencia mínima</label><input type="number" min="0" step="0.001" class="form-control" name="min_stock" value="{{ old('min_stock', $item?->min_stock) }}"></div>
</div>
<div class="form-check form-switch mb-3"><input type="checkbox" class="form-check-input" name="status" id="status" @checked(old('status', $item?->status ?? true))><label class="form-check-label" for="status">Activo</label></div>
@if(!$item)
<script>
document.getElementById('base_unit').addEventListener('change', function () {
    document.getElementById('stock_unit').value = this.value;
});
</script>
@endif
