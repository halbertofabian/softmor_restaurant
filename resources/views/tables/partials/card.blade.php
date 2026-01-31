@php
$isActive = $table->is_active;
$status = $table->status;

// Define colors based on status using our custom variables
$statusColor = 'var(--text-secondary)';
$statusText = 'Inactiva';
$statusBg = 'rgba(255, 255, 255, 0.05)';

if ($isActive) {
    if ($status == 'free') {
        $statusColor = 'var(--status-free)';
        $statusText = 'Disponible';
        $statusBg = 'rgba(16, 185, 129, 0.1)';
    } elseif ($status == 'occupied') {
        $statusColor = 'var(--status-occupied)';
        $statusText = 'Ocupada';
        $statusBg = 'rgba(239, 68, 68, 0.1)';
    } else {
        $statusColor = '#f59e0b'; // Warning
        $statusText = 'Reservada';
        $statusBg = 'rgba(245, 158, 11, 0.1)';
    }
}
@endphp

<div class="card h-100 border-0 shadow-sm" style="background: var(--card-bg); border: 1px solid var(--border-subtle) !important;">
    <!-- Status Strip -->
    <div class="position-absolute top-0 start-0 w-100 rounded-top" style="height: 3px; background-color: {{ $statusColor }}; box-shadow: 0 2px 8px {{ $statusColor }};"></div>
    
    <div class="card-body p-3 pt-4 d-flex flex-column h-100">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1 fw-bold text-white d-flex align-items-center">
                    {{ $table->name }}
                    @if($table->zone)
                    <span class="badge ms-2 small" style="background: rgba(255,255,255,0.1); color: var(--text-secondary); font-weight: 500; font-size: 0.65rem;">{{ $table->zone }}</span>
                    @endif
                </h5>
                <small class="d-flex align-items-center" style="color: var(--text-secondary);">
                    <i class="ti tabler-users me-1" style="font-size: 0.9rem;"></i> 
                    {{ $table->capacity }} pax
                </small>
            </div>
            
            @unless(auth()->user()->hasRole('mesero'))
            <div class="dropdown">
                <button class="btn btn-sm btn-icon rounded-pill p-0" type="button" data-bs-toggle="dropdown" style="color: var(--text-secondary);">
                    <i class="ti tabler-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="background: var(--card-bg); border-color: var(--border-subtle);">
                    <li><a class="dropdown-item text-white hover-primary" href="{{ route('tables.edit', $table) }}"><i class="ti tabler-edit me-2"></i>Editar</a></li>
                    <li>
                        <form action="{{ route('tables.destroy', $table) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('¿Eliminar?')"><i class="ti tabler-trash me-2"></i>Eliminar</button>
                        </form>
                    </li>
                </ul>
            </div>
            @endunless
        </div>

        <div class="mt-auto d-flex flex-column gap-2">
             @if(!$isActive)
                <div class="text-center py-1 rounded" style="background: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}; opacity: 0.5;">
                    <small class="fw-bold">{{ $statusText }}</small>
                </div>
            @else
                @switch($status)
                    @case('free')
                        <form action="{{ route('tables.occupy', $table) }}" method="POST" class="w-100">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn w-100 fw-bold d-flex align-items-center justify-content-center" style="border: 1px solid var(--status-free); color: var(--status-free); background: transparent; transition: all 0.2s;">
                                <i class="ti tabler-plus me-1"></i> Ocupar
                            </button>
                        </form>
                        @break
                    @case('occupied')
                        <div class="w-100">
                            @if($activeOrder = $table->orders->where('status', 'open')->first())
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-subtle);">
                                    <div class="d-flex align-items-center" style="color: var(--status-occupied);">
                                        <i class="ti tabler-clock me-1"></i>
                                        <small class="fw-bold">{{ $activeOrder->created_at->format('H:i') }}</small>
                                    </div>
                                    <div class="badge text-black fw-bold" style="background: var(--primary);">
                                        ${{ number_format($activeOrder->total, 2) }}
                                    </div>
                                </div>

                                @if(auth()->user()->hasRole('mesero'))
                                    {{-- Mesero: Solo Ve Comanda --}}
                                    <a href="{{ route('orders.mobile', $activeOrder) }}" class="btn w-100 fw-bold mb-2" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #000; border: none;">
                                        <i class="ti tabler-clipboard-list me-1"></i> Ver Orden
                                    </a>
                                @else
                                    {{-- Cajero/Admin --}}
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('pos.checkout', $activeOrder) }}" class="btn fw-bold" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #000; border: none;">
                                            <i class="ti tabler-cash me-1"></i> Cobrar
                                        </a>
                                        <a href="{{ route('orders.pre-check', $activeOrder) }}" target="_blank" class="btn btn-sm" style="border: 1px solid var(--border-subtle); color: var(--text-secondary);">
                                            <i class="ti tabler-printer me-1"></i> Pre-Cuenta
                                        </a>
                                    </div>
                                @endif
                                
                            @else
                                <form action="{{ route('orders.store') }}" method="POST" class="w-100">
                                    @csrf
                                    <input type="hidden" name="table_id" value="{{ $table->id }}">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                                        <i class="ti tabler-plus me-1"></i> Recuperar
                                    </button>
                                </form>
                            @endif
                        </div>
                        @break
                    @case('reserved')
                         <form action="{{ route('tables.release', $table) }}" method="POST" class="w-100">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn w-100" style="border: 1px solid #f59e0b; color: #f59e0b; background: transparent;">
                                <i class="ti tabler-lock-open me-1"></i> Liberar
                            </button>
                        </form>
                        @break
                @endswitch
            @endif
        </div>
    </div>
</div>
