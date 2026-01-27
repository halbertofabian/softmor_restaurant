@extends('layouts.auth')

@section('title', 'Crear Primera Sucursal')

@section('content')
<div class="auth-card rounded-2xl p-8 w-full relative overflow-hidden">
    <!-- Efecto de brillo superior -->
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#FFAB1D] to-transparent opacity-50"></div>
    
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold mb-2">¡Casi listo! 🚀</h2>
        <p class="text-gray-400 text-sm">Crea tu primera sucursal para comenzar a trabajar.</p>
    </div>

    <form action="{{ route('setup-branch.store') }}" method="POST" class="space-y-5">
        @csrf
        
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 pl-1">Nombre de la Sucursal</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-[#FFAB1D]">
                    <i class="ti ti-building-store text-gray-500"></i>
                </div>
                <input type="text" 
                       name="name" 
                       placeholder="Ej. Matriz, Centro, Sucursal Norte"
                       required
                       autofocus
                       class="form-input w-full pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-[#FFAB1D]/20" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 pl-1">Dirección (Opcional)</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-[#FFAB1D]">
                    <i class="ti ti-map-pin text-gray-500"></i>
                </div>
                <input type="text" 
                       name="address" 
                       placeholder="Calle Principal #123"
                       class="form-input w-full pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-[#FFAB1D]/20" />
            </div>
        </div>

        <button type="submit" class="btn-primary-custom w-full py-4 rounded-xl flex items-center justify-center gap-2 mt-2 group">
            <span>Crear e Iniciar</span>
            <i class="ti ti-rocket transition-transform group-hover:-translate-y-1 group-hover:translate-x-1"></i>
        </button>
    </form>
</div>
@endsection
