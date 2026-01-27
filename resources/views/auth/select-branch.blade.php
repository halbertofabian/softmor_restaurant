@extends('layouts.auth')

@section('title', 'Seleccionar Sucursal')

@section('content')
<div class="auth-card rounded-2xl p-8 w-full">
    <div class="text-center mb-8">
        <h3 class="text-2xl font-bold text-white mb-2">Bienvenido 👋</h3>
        <p class="text-gray-400">Selecciona una sucursal para continuar</p>
    </div>

    <form action="{{ route('branches.start') }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label for="branchSelect" class="block text-sm font-medium text-gray-300 mb-2">Sucursal</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ti ti-building-store text-gray-400 text-lg"></i>
                </div>
                <select name="branch_id" id="branchSelect" required autofocus
                    class="form-input w-full rounded-xl pl-10 pr-4 py-3 appearance-none bg-opacity-20 cursor-pointer focus:ring-2 focus:ring-[#FFAB1D]">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" class="bg-[#18181b] text-white py-2">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="ti ti-chevron-down text-gray-400"></i>
                </div>
            </div>
            
            <div class="mt-3 flex items-start gap-2 text-sm text-gray-500">
                <i class="ti ti-map-pin mt-0.5"></i>
                <span id="selectedAddress" class="leading-tight">{{ $branches->first()->address ?? 'Ubicación' }}</span>
            </div>
        </div>

        <button type="submit" class="w-full btn-primary-custom rounded-xl py-3.5 px-4 flex items-center justify-center gap-2 group">
            <span>Ingresar al Sistema</span>
            <i class="ti ti-arrow-right group-hover:translate-x-1 transition-transform"></i>
        </button>
    </form>
</div>

<div class="text-center mt-8">
    <form action="{{ route('logout') }}" method="POST" class="inline-block">
        @csrf
        <button type="submit" class="text-gray-500 hover:text-white text-sm font-medium transition-colors flex items-center gap-2">
            <i class="ti ti-logout"></i> Cerrar Sesión
        </button>
    </form>
</div>

<!-- Simple Script to update address on change -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const branches = @json($branches->map(fn($b) => ['id' => $b->id, 'address' => $b->address ?? 'Sin dirección']));
        const select = document.getElementById('branchSelect');
        const addressSpan = document.getElementById('selectedAddress');

        select.addEventListener('change', function() {
            const selectedId = parseInt(this.value);
            const branch = branches.find(b => b.id === selectedId);
            if(branch) {
                addressSpan.textContent = branch.address;
            }
        });
    });
</script>
@endsection
