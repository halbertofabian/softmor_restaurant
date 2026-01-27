<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Softmor Restaurant - El Cerebro de tu Negocio</title>

    <!-- Tailwind CSS para diseño moderno y rápido -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        :root {
            --primary: #FFAB1D;
            --primary-dark: #E59A1A;
            --dark-bg: #09090b;
            --card-bg: #18181b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--dark-bg);
            color: #fafafa;
        }

        /* Glassmorphism Effect */
        .glass-nav {
            background: rgba(9, 9, 11, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Hero Mesh Gradient */
        .hero-mesh {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(at 0% 0%, rgba(255, 171, 29, 0.15) 0, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(255, 171, 29, 0.1) 0, transparent 50%);
            z-index: -1;
        }

        /* Animación de entrada */
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Tarjetas Estilo Bento */
        .bento-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bento-card:hover {
            border-color: rgba(255, 171, 29, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .custom-button-primary {
            background: var(--primary);
            color: #000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 171, 29, 0.2);
        }

        .custom-button-primary:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(255, 171, 29, 0.3);
        }

        /* Grid Pattern */
        .bg-grid {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.02) 1px, transparent 1px);
        }
    </style>
</head>
<body class="bg-grid">

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 glass-nav">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-[#FFAB1D] rounded-xl flex items-center justify-center shadow-lg transition-transform group-hover:rotate-12">
                    <i class="ti ti-chef-hat text-black text-xl"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-white">
                    Softmor Restaurant<span class="text-[#FFAB1D]">.</span>
                </span>
            </a>

            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-sm font-medium text-gray-400 hover:text-[#FFAB1D] transition-colors">Características</a>
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="custom-button-primary px-6 py-2.5 rounded-full text-sm font-bold">
                    Probar Gratis
                </a>
            </div>

            <!-- Mobile Menu Icon -->
            <button id="mobile-menu-btn" class="md:hidden text-white text-2xl relative z-50">
                <i class="ti ti-menu-2"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="fixed inset-0 z-40 bg-black/95 backdrop-blur-xl transform translate-x-full transition-transform duration-300 md:hidden flex flex-col items-center justify-center gap-8">
        <a href="#features" class="text-2xl font-medium text-gray-400 hover:text-[#FFAB1D] transition-colors mobile-link">Características</a>
        <a href="{{ route('login') }}" class="text-2xl font-medium text-gray-400 hover:text-white transition-colors mobile-link">Iniciar sesión</a>
        <a href="{{ route('register') }}" class="custom-button-primary px-8 py-3 rounded-full text-lg font-bold mobile-link">
            Probar Gratis
        </a>
    </div>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 md:pt-48 md:pb-32 overflow-hidden">
        <div class="hero-mesh"></div>
        
        <div class="container mx-auto px-6 text-center lg:text-left flex flex-col lg:flex-row items-center gap-16">
            <div class="lg:w-1/2 fade-in">
                <!--<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-500 text-xs font-bold uppercase tracking-wider mb-6">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                    </span>
                    Nuevo: Módulo de Facturación Automática
                </div>-->
                
                <h1 class="text-5xl md:text-7xl font-extrabold leading-[1.1] mb-6 tracking-tighter">
                    El cerebro de tu <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#FFAB1D] to-orange-400">Restaurante.</span>
                </h1>
                
                <p class="text-gray-400 text-lg md:text-xl mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Controla comandas, gestión de inventarios y personal en una plataforma diseñada para la velocidad. Menos fricción, más ventas.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 mb-12">
                    <a href="{{ route('register') }}" class="custom-button-primary w-full sm:w-auto px-8 py-4 rounded-2xl font-bold flex items-center justify-center gap-2">
                        Comenzar ahora
                        <i class="ti ti-arrow-right"></i>
                    </a>
                    <a href="#features" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all font-bold text-white text-center">
                        Ver funciones
                    </a>
                </div>

                <div class="flex flex-wrap justify-center lg:justify-start gap-6">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="ti ti-circle-check-filled text-[#FFAB1D]"></i> 3 Meses Gratis
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="ti ti-circle-check-filled text-[#FFAB1D]"></i> Sin tarjeta de crédito
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="ti ti-circle-check-filled text-[#FFAB1D]"></i> Multi-plataforma
                    </div>
                </div>
            </div>

            <!-- Dashboard Preview -->
            <div class="lg:w-1/2 relative fade-in" style="animation-delay: 0.2s">
                <div class="relative z-10 p-2 bg-gradient-to-br from-white/10 to-transparent rounded-[2.5rem] border border-white/10 shadow-2xl">
                    <img src="{{ asset('assets/img/dashboard_preview.png') }}" alt="Dashboard Preview" class="rounded-[2rem] w-full shadow-inner shadow-white/5">
                </div>
                <!-- Decorative Elements -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-500/20 blur-[60px] rounded-full"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-orange-500/10 blur-[80px] rounded-full"></div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-[#09090b]">
        <div class="container mx-auto px-6">
            <div class="text-center mb-20">
                <h2 class="text-[#FFAB1D] font-bold text-sm uppercase tracking-widest mb-3">Poder sin límites</h2>
                <h3 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight">Optimiza cada segundo</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bento-card p-8 rounded-[2rem] flex flex-col items-start gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 flex items-center justify-center text-[#FFAB1D]">
                        <i class="ti ti-device-tablet text-3xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-3">Comanda Digital</h4>
                        <p class="text-gray-400 leading-relaxed text-sm">Elimina los errores de papel. Toma pedidos desde cualquier tablet o móvil con sincronización instantánea.</p>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bento-card p-8 rounded-[2rem] flex flex-col items-start gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 flex items-center justify-center text-[#FFAB1D]">
                        <i class="ti ti-chef-hat text-3xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-3">Zonas de Preparación</h4>
                        <p class="text-gray-400 leading-relaxed text-sm">Divide pedidos automáticamente. Las bebidas van a barra y los platos a cocina sin mover un dedo.</p>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bento-card p-8 rounded-[2rem] flex flex-col items-start gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 flex items-center justify-center text-[#FFAB1D]">
                        <i class="ti ti-printer text-3xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-3">Auto-Ticket</h4>
                        <p class="text-gray-400 leading-relaxed text-sm">Impresión inteligente por área. Cada estación recibe solo lo que necesita preparar en tiempo real.</p>
                    </div>
                </div>

                <!-- Card 4 (Large / Highlighting Multi-tenancy) -->
                <div class="bento-card p-8 rounded-[2rem] md:col-span-2 flex flex-col md:flex-row items-center gap-10">
                    <div class="md:w-1/2">
                        <div class="w-14 h-14 rounded-2xl bg-orange-500/10 flex items-center justify-center text-[#FFAB1D] mb-6">
                            <i class="ti ti-layout-grid text-3xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">Control Visual de Mesas</h4>
                        <p class="text-gray-400 leading-relaxed text-sm">Mapa interactivo de tu salón. Identifica tiempos de espera, mesas libres y cierres de cuenta de un vistazo.</p>
                    </div>
                    <div class="md:w-1/2 grid grid-cols-2 gap-3">
                        <div class="h-24 bg-orange-500/20 rounded-xl flex items-center justify-center border border-orange-500/20 text-orange-500 font-bold">OCUPADA</div>
                        <div class="h-24 bg-emerald-500/20 rounded-xl flex items-center justify-center border border-emerald-500/20 text-emerald-500 font-bold">LIBRE</div>
                        <div class="h-24 bg-gray-500/20 rounded-xl flex items-center justify-center border border-gray-500/20 text-gray-400 font-bold">CERRANDO</div>
                        <div class="h-24 bg-blue-500/20 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-500 font-bold">RESERVADA</div>
                    </div>
                </div>

                <!-- Card 5 -->
                <div class="bento-card p-8 rounded-[2rem] flex flex-col items-start gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 flex items-center justify-center text-[#FFAB1D]">
                        <i class="ti ti-shield-lock text-3xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-3">Aislamiento Total</h4>
                        <p class="text-gray-400 leading-relaxed text-sm">Arquitectura Multi-tenant que garantiza que tus datos de negocio y clientes estén siempre seguros.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-b from-[#09090b] to-black">
        <div class="container mx-auto px-6 text-center">
            <div class="max-w-4xl mx-auto bg-gradient-to-br from-[#FFAB1D] to-orange-600 rounded-[3rem] p-12 md:p-20 relative overflow-hidden">
                <div class="absolute inset-0 bg-grid opacity-10"></div>
                
                <div class="relative z-10">
                    <h2 class="text-4xl md:text-6xl font-black text-black mb-6 tracking-tighter">
                        ¿Listo para transformar <br> tu operación?
                    </h2>
                    <p class="text-black/70 text-lg md:text-xl mb-12 max-w-2xl mx-auto font-medium">
                        Únete a los restaurantes que han reducido sus tiempos de servicio en un 30% desde la primera semana.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('register') }}" class="px-10 py-5 bg-black text-white rounded-2xl font-bold text-lg hover:scale-105 transition-transform">
                            Crear cuenta gratis
                        </a>
                        <a href="{{ route('login') }}" class="px-10 py-5 bg-black/10 text-black border border-black/10 rounded-2xl font-bold text-lg hover:bg-black/20 transition-all">
                            Acceso clientes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-white/5 bg-black">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[#FFAB1D] rounded-lg flex items-center justify-center">
                        <i class="ti ti-chef-hat text-black text-sm"></i>
                    </div>
                    <span class="text-lg font-bold text-white">Softmor Restaurant.</span>
                </div>
                
                <div class="flex gap-8 text-gray-500 text-sm">
                    <a href="#" class="hover:text-[#FFAB1D] transition-colors">Términos</a>
                    <a href="#" class="hover:text-[#FFAB1D] transition-colors">Privacidad</a>
                    <a href="#" class="hover:text-[#FFAB1D] transition-colors">Soporte</a>
                </div>

                <p class="text-gray-600 text-sm">
                    &copy; {{ date('Y') }} Softmor Restaurant. <br class="md:hidden"> Todos los derechos reservados.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileLinks = document.querySelectorAll('.mobile-link');
            let isMenuOpen = false;

            if (menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', () => {
                    isMenuOpen = !isMenuOpen;
                    if (isMenuOpen) {
                        mobileMenu.classList.remove('translate-x-full');
                        menuBtn.innerHTML = '<i class="ti ti-x"></i>';
                        // Prevent scrolling when menu is open
                        document.body.style.overflow = 'hidden';
                    } else {
                        mobileMenu.classList.add('translate-x-full');
                        menuBtn.innerHTML = '<i class="ti ti-menu-2"></i>';
                        document.body.style.overflow = '';
                    }
                });

                mobileLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        isMenuOpen = false;
                        mobileMenu.classList.add('translate-x-full');
                        menuBtn.innerHTML = '<i class="ti ti-menu-2"></i>';
                        document.body.style.overflow = '';
                    });
                });
            }
        });
    </script>
</body>
</html>