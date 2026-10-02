@if(session('inventory_shortages'))
<div class="alert alert-warning" data-inventory-warning>
    <h6 class="alert-heading mb-2">Existencia insuficiente</h6>
    <ul class="mb-3">
        @foreach(session('inventory_shortages') as $shortage)
            <li>{{ $shortage['name'] }}: disponible {{ number_format($shortage['available'], 3) }} {{ $shortage['unit'] }}, requerido {{ number_format($shortage['required'], 3) }} {{ $shortage['unit'] }}, quedará en {{ number_format($shortage['resulting'], 3) }} {{ $shortage['unit'] }}.</li>
        @endforeach
    </ul>
    <form method="POST" action="{{ session('inventory_confirm_action') }}" data-mobile-order-async>@csrf
        <input type="hidden" name="allow_negative_inventory" value="1">
        <button class="btn btn-warning">Enviar de todos modos</button>
    </form>
</div>
@endif
