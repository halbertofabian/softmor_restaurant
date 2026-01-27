@extends('layouts.auth')
@section('title', 'Registro Gratis')

@section('content')
    <div class="auth-card rounded-3xl p-8 w-full max-w-lg mx-auto relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute top-0 left-0 w-32 h-32 bg-orange-500/10 blur-[50px] rounded-full pointer-events-none"></div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">Comienza tu Prueba Gratis</h1>
            <p class="text-gray-400 text-sm">3 meses sin costo. Tarjeta de crédito no requerida.</p>
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

        <form action="{{ route('register') }}" method="POST" class="flex flex-col gap-5">
            @csrf
            
            <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">Nombre del Negocio / Admin</label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-500">
                        <i class="ti ti-building-store"></i>
                    </span>
                    <input type="text" name="name" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="Ej. Restaurante La Plaza" value="{{ old('name') }}" required autofocus>
                </div>
            </div>

            <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-500">
                        <i class="ti ti-mail"></i>
                    </span>
                    <input type="email" name="email" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="admin@restaurante.com" value="{{ old('email') }}" required>
                </div>
            </div>

            <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">WhatsApp</label>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1 relative">
                        <select name="country_code" class="form-input w-full rounded-xl py-3 px-4 focus:ring-0 appearance-none bg-transparent" required>
                            <!-- North America -->
                            <option value="52" class="text-black">Mexico (+52)</option>
                            <option value="1" class="text-black">USA/Canada (+1)</option>
                            <!-- Central America -->
                            <option value="502" class="text-black">Guatemala (+502)</option>
                            <option value="503" class="text-black">El Salvador (+503)</option>
                            <option value="504" class="text-black">Honduras (+504)</option>
                            <option value="505" class="text-black">Nicaragua (+505)</option>
                            <option value="506" class="text-black">Costa Rica (+506)</option>
                            <option value="507" class="text-black">Panama (+507)</option>
                            <!-- Caribbean -->
                            <option value="1809" class="text-black">Dominican Rep. (+1809)</option>
                            <option value="53" class="text-black">Cuba (+53)</option>
                            <!-- South America -->
                            <option value="54" class="text-black">Argentina (+54)</option>
                            <option value="591" class="text-black">Bolivia (+591)</option>
                            <option value="55" class="text-black">Brazil (+55)</option>
                            <option value="56" class="text-black">Chile (+56)</option>
                            <option value="57" class="text-black">Colombia (+57)</option>
                            <option value="593" class="text-black">Ecuador (+593)</option>
                            <option value="595" class="text-black">Paraguay (+595)</option>
                            <option value="51" class="text-black">Peru (+51)</option>
                            <option value="598" class="text-black">Uruguay (+598)</option>
                            <option value="58" class="text-black">Venezuela (+58)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <i class="ti ti-chevron-down text-gray-500"></i>
                        </div>
                    </div>
                    <div class="col-span-2 relative">
                        <span class="absolute left-4 top-3.5 text-gray-500">
                            <i class="ti ti-brand-whatsapp"></i>
                        </span>
                        <input type="text" name="whatsapp_number" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="5555555555" value="{{ old('whatsapp_number') }}" required>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-400 text-sm font-medium mb-2">Contraseña</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-500">
                            <i class="ti ti-lock"></i>
                        </span>
                        <input type="password" name="password" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="••••••••" required>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-400 text-sm font-medium mb-2">Confirmar</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-500">
                            <i class="ti ti-lock-check"></i>
                        </span>
                        <input type="password" name="password_confirmation" class="form-input w-full rounded-xl py-3 pl-11 pr-4 focus:ring-0" placeholder="••••••••" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom w-full py-3.5 rounded-xl text-black mt-2 flex items-center justify-center gap-2">
                Crear Cuenta
                <i class="ti ti-rocket"></i>
            </button>
        </form>

        <div class="mt-8 text-center border-t border-white/5 pt-6">
            <p class="text-gray-400 text-sm">
                ¿Ya tienes cuenta? 
                <a href="{{ route('login') }}" class="text-[#FFAB1D] hover:underline font-semibold">Inicia Sesión</a>
            </p>
        </div>
    </div>
@endsection
