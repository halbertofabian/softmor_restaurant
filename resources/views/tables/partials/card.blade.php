@php
$isActive = $table->is_active;
$status = $table->status;
$borderColor = !$isActive ? 'secondary' : ($status == 'free' ? 'success' : ($status == 'occupied' ? 'danger' : 'warning'));
// Classes for badge backgrounds
$badgeClass = !$isActive ? 'bg-label-secondary' : ($status == 'free' ? 'bg-label-success' : ($status == 'occupied' ? 'bg-label-danger' : 'bg-label-warning'));
@endphp

<div class="card h-100 position-relative border-0 shadow-sm transition-all hover-transform">
    <!-- Status Indicator Line -->
    <div class="position-absolute top-0 start-0 w-100 rounded-top" style="height: 4px; background-color: var(--bs-{{ $borderColor }});"></div>
    
    <div class="card-body p-3 pt-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1 fw-bold d-flex align-items-center">
                    {{ $table->name }}
                    @if($table->zone)
                    <span class="badge bg-label-secondary ms-2 small" style="font-size: 0.65rem;">{{ $table->zone }}</span>
                    @endif
                </h5>
                <small class="text-muted d-flex align-items-center">
                    <i class="ti tabler-users me-1 text-body" style="font-size: 0.9rem;"></i> 
                    {{ $table->capacity }} personas
                </small>
            </div>
            
            @unless(auth()->user()->hasRole('mesero'))
            <div class="dropdown">
                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" data-bs-toggle="dropdown">
                    <i class="ti tabler-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('tables.edit', $table) }}"><i class="ti tabler-edit me-2"></i>Editar</a></li>
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

        <div class="d-flex flex-column gap-2">
             @if(!$isActive)
                <span class="badge w-100 bg-secondary">Inactiva</span>
            @else
                @switch($status)
                    @case('free')
                        <div class="text-center py-2 mb-2">
                            <span class="badge bg-label-success rounded-pill px-3">Disponible</span>
                        </div>
                        <form action="{{ route('tables.occupy', $table) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-outline-success w-100 fw-bold">
                                <i class="ti tabler-armchair me-1"></i> Ocupar Mesa
                            </button>
                        </form>
                        @break
                    @case('occupied')
                        <div class="w-100">
                            @if($activeOrder = $table->orders->where('status', 'open')->first())
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-lighter rounded">
                                    <div class="d-flex align-items-center text-danger">
                                        <i class="ti tabler-clock me-1"></i>
                                        <small class="fw-bold">{{ $activeOrder->created_at->format('H:i') }}</small>
                                    </div>
                                    <div class="badge bg-primary fs-6">
                                        ${{ number_format($activeOrder->total, 2) }}
                                    </div>
                                </div>

                                @if(auth()->user()->hasRole('mesero'))
                                    {{-- Mesero: Solo Ve Comanda --}}
                                    <a href="{{ route('orders.mobile', $activeOrder) }}" class="btn btn-primary w-100 fw-bold mb-2">
                                        <i class="ti tabler-clipboard-list me-1"></i> Ver Comanda
                                    </a>
                                @else
                                    {{-- Cajero/Admin: Cobrar como botón principal --}}
                                    <a href="{{ route('pos.checkout', $activeOrder) }}" class="btn btn-primary w-100 fw-bold mb-2">
                                        <i class="ti tabler-cash me-1"></i> Cobrar
                                    </a>
                                    <a href="{{ route('orders.pre-check', $activeOrder) }}" target="_blank" class="btn btn-outline-secondary w-100 btn-sm">
                                        <i class="ti tabler-printer me-1"></i> Ver Pre-Cuenta
                                    </a>
                                @endif
                                
                            @else
                                <form action="{{ route('orders.store') }}" method="POST" class="w-100">
                                    @csrf
                                    <input type="hidden" name="table_id" value="{{ $table->id }}">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                                        <i class="ti tabler-plus me-1"></i> Nueva Comanda
                                    </button>
                                </form>
                            @endif
                        </div>
                        @break
                    @case('reserved')
                        <div class="text-center py-2 mb-2">
                             <span class="badge bg-label-warning w-100">Reservada</span>
                        </div>
                         <form action="{{ route('tables.release', $table) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="ti tabler-lock-open me-1"></i> Liberar
                            </button>
                        </form>
                        @break
                @endswitch
            @endif
        </div>
    </div>
</div>
