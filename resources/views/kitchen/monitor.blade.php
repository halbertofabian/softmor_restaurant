@extends('layouts.master')
@section('title', 'Monitor: ' . $area->name)
@section('content')
<div class="row align-items-center mb-4">
    <div class="col-6">
        <h4 class="mb-0">
            <span class="text-muted fw-light">Monitor /</span> {{ $area->name }}
        </h4>
        <small class="text-muted">Actualización automática cada 30s</small>
    </div>
    <div class="col-6 text-end">
        <a href="{{ route('kitchen.index') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i> Salir
        </a>
    </div>
</div>

<div class="row" id="orders-grid">
    @forelse($orders as $order)
    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card h-100 border-top border-primary border-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                <h6 class="mb-0 fw-bold">Mesa {{ $order->table->name }}</h6>
                <span class="badge bg-label-primary">#{{ $order->id }}</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($order->details as $detail)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-truncate" style="max-width: 80%;">
                                <span class="badge bg-primary rounded-pill me-1">{{ $detail->quantity }}</span>
                                {{ $detail->product_name }}
                            </h6>
                            
                            @if($detail->updated_at->diffInMinutes(now()) < 5)
                                <span class="badge bg-danger animate__animated animate__flash animate__infinite infinite">NUEVO</span>
                            @else
                                <small class="text-muted">{{ $detail->updated_at->format('H:i') }}</small>
                            @endif
                        </div>
                        @if($detail->notes)
                        <div class="alert alert-warning p-1 mb-0 mt-1 small">
                            <i class="ti tabler-note me-1"></i> {{ $detail->notes }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center text-muted small">
                <i class="ti tabler-clock me-1"></i> {{ $order->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="text-muted display-6 mb-3"><i class="ti tabler-cup"></i></div>
        <h3>No hay comandas pendientes</h3>
        <p>Todo está tranquilo en {{ $area->name }}.</p>
    </div>
    @endforelse
</div>

@section('scripts')
<style>
@keyframes flash {
  0%, 50%, 100% {
    opacity: 1;
  }
  25%, 75% {
    opacity: 0;
  }

}
.animate__flash {
  animation-name: flash;
  animation-duration: 2s;
}
</style>
<script>
    // Auto-refresh every 30 seconds
    setTimeout(function(){
       window.location.reload(1);
    }, 30000);
</script>
@endsection

@endsection
