@extends('layouts.auth')
@section('title', 'Iniciar Sesión')

@section('content')
    <div class="auth-card rounded-3xl p-8 w-full relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute top-0 right-0 w-20 h-20 bg-orange-500/10 blur-[40px] rounded-full pointer-events-none"></div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">Bienvenido de nuevo</h1>
            <p class="text-gray-400 text-sm">Ingresa tus credenciales para acceder</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
                <ul class="text-red-400 text-sm list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="flex flex-col gap-5">
            @csrf
            <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-500">
                        <i class="ti ti-mail"></i>
                    </span>
                    <input type="email" name="email" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="tu@correo.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-gray-400 text-sm font-medium">Contraseña</label>
                </div>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-500">
                        <i class="ti ti-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom w-full py-3.5 rounded-xl text-black mt-2 flex items-center justify-center gap-2">
                Ingresar al Sistema
                <i class="ti ti-arrow-right"></i>
            </button>
        </form>

        <div class="mt-8 text-center border-t border-white/5 pt-6">
            <p class="text-gray-400 text-sm">
                ¿No tienes cuenta? 
                <a href="{{ route('register') }}" class="text-[#FFAB1D] hover:underline font-semibold">Crea una gratis</a>
            </p>
        </div>
    </div>
@endsection
