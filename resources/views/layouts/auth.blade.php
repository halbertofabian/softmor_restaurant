<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/icon_gestionalfood.png') }}" />
    
    <!-- Tailwind CSS -->
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

        .bg-grid {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.02) 1px, transparent 1px);
        }

        .auth-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 171, 29, 0.05);
            box-shadow: 0 0 0 4px rgba(255, 171, 29, 0.1);
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 171, 29, 0.2);
            font-weight: 700;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(255, 171, 29, 0.3);
        }
        
        .hero-mesh {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(at 50% 0%, rgba(255, 171, 29, 0.1) 0, transparent 50%);
            z-index: -1;
        }
    </style>
</head>
<body class="bg-grid min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden">
    
    <div class="hero-mesh"></div>

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg transition-transform group-hover:rotate-12">
                    <img src="{{ asset('assets/img/icon_gestionalfood.png') }}" alt="{{ config('app.name') }}" class="w-10 h-10 rounded-xl">
                </div>
                <span class="text-2xl font-extrabold tracking-tight text-white">
                    {{ config('app.name') }}<span class="text-[#FFAB1D]">.</span>
                </span>
            </a>
        </div>

        @yield('content')
        
    </div>

    @yield('scripts')
</body>
</html>
