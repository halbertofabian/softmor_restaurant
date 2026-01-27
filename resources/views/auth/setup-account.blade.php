@extends('layouts.auth')

@section('title', 'Activar Cuenta - Softmor')

@section('content')
<div class="auth-card rounded-2xl p-8 w-full relative overflow-hidden">
    <!-- Efecto de brillo superior -->
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#FFAB1D] to-transparent opacity-50"></div>
    
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold mb-2">Bienvenido, {{ explode(' ', $user->name)[0] }}! 👋</h2>
        <p class="text-gray-400 text-sm">Configura tu contraseña para acceder a tu panel de control.</p>
    </div>

    @if (session('error'))
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 flex items-center gap-3 text-sm">
            <i class="ti ti-alert-circle text-xl"></i>
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('setup-account.store', $token) }}" method="POST" class="space-y-5">
        @csrf
        
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 pl-1">Correo Electrónico</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ti ti-mail text-gray-500"></i>
                </div>
                <input type="email" 
                       value="{{ $user->email }}" 
                       disabled 
                       class="form-input w-full pl-11 pr-4 py-3 rounded-xl opacity-60 cursor-not-allowed" />
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                    <i class="ti ti-lock text-gray-500 text-xs"></i>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 pl-1">Nueva Contraseña</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-[#FFAB1D]">
                    <i class="ti ti-key text-gray-500"></i>
                </div>
                <input type="password" 
                       name="password" 
                       id="password"
                       placeholder="Mínimo 8 caracteres"
                       required
                       class="form-input w-full pl-11 pr-10 py-3 rounded-xl focus:ring-2 focus:ring-[#FFAB1D]/20" />
                <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors">
                    <i class="ti ti-eye-off" id="icon-password"></i>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 pl-1">Confirmar Contraseña</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-[#FFAB1D]">
                    <i class="ti ti-check text-gray-500"></i>
                </div>
                <input type="password" 
                       name="password_confirmation" 
                       id="password_confirmation"
                       placeholder="Repite tu contraseña"
                       required
                       class="form-input w-full pl-11 pr-10 py-3 rounded-xl focus:ring-2 focus:ring-[#FFAB1D]/20" />
                <button type="button" onclick="togglePassword('password_confirmation')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-white transition-colors">
                    <i class="ti ti-eye-off" id="icon-password_confirmation"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary-custom w-full py-4 rounded-xl flex items-center justify-center gap-2 mt-2 group">
            <span>Activar Cuenta y Acceder</span>
            <i class="ti ti-arrow-right transition-transform group-hover:translate-x-1"></i>
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <p class="text-xs text-gray-500">
            &copy; {{ date('Y') }} Restaurant Softmor. Todos los derechos reservados.
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById('icon-' + inputId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ti-eye-off');
            icon.classList.add('ti-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('ti-eye');
            icon.classList.add('ti-eye-off');
        }
    }
</script>
@endsection
