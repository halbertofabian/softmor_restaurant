<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $branch->name }} - Menú Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabler-icons-webfont@latest/tabler-icons.min.css">
    
    <style>
        :root {
            /* Palette: Dark Premium with #FFAB1D */
            --primary: #FFAB1D;
            --primary-contrast: #000000; /* Black text on the bright primary color */
            --background: #121212;
            --surface: #1E1E1E;
            --text-main: #FFFFFF;
            --text-secondary: #A0A0A0;
            --border: #2D2D2D;
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.5); /* Stronger shadow for dark mode */
        }

        html, body {
            overflow-x: hidden;
            width: 100%;
            position: relative;
            background-color: var(--background);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding-bottom: 80px;
            -webkit-font-smoothing: antialiased;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        /* Hero Section */
        .hero {
            background-color: var(--surface);
            padding: 2rem 1.5rem 1rem;
            border-bottom-left-radius: var(--radius-lg);
            border-bottom-right-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: relative;
            z-index: 10;
            width: 100%;
        }

        .restaurant-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .restaurant-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .restaurant-status {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 4px 12px;
            background-color: rgba(255, 171, 29, 0.15); /* Primary with opacity */
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            margin-top: 1rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background-color: var(--background);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-main);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            background-color: var(--surface);
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 171, 29, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        /* Category Navigation */
        .category-nav {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            padding: 1rem 1.5rem;
            width: 100%;
            scrollbar-width: none; 
            -ms-overflow-style: none;
            position: sticky;
            top: 0;
            background: var(--background); /* Match dark background */
            border-bottom: 1px solid var(--border);
            z-index: 100;
            margin-bottom: 0.5rem;
            white-space: nowrap;
        }

        .category-nav::-webkit-scrollbar {
            display: none;
        }

        .category-pill {
            flex: 0 0 auto;
            padding: 10px 20px;
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .category-pill.active {
            background-color: var(--primary);
            color: var(--primary-contrast); /* Black text for contrast */
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 171, 29, 0.3);
        }

        /* Content Area */
        .container {
            padding: 0 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .category-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin: 2rem 0 1rem;
            color: var(--primary);
            display: flex;
            align-items: center;
        }

        .category-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: var(--border);
            margin-left: 1rem;
        }

        /* Product Card */
        .product-card {
            background-color: var(--surface);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            border: 1px solid var(--border);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:active {
            transform: scale(0.98);
        }

        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-header {
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .product-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
        }

        .product-price {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .product-image {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background-color: #f1f1f1;
            flex-shrink: 0;
        }

        /* Skeleton Loading Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .loading .product-image,
        .loading .product-name,
        .loading .product-desc {
            background: linear-gradient(to right, #eff1f3 4%, #e2e2e2 25%, #eff1f3 36%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite linear;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-secondary);
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #DDD;
        }

        @media (min-width: 768px) {
            .product-image {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <header class="hero">
        <div class="restaurant-brand">
            <h1 class="restaurant-name">{{ $branch->name }}</h1>
            <span class="restaurant-status">Abierto</span>
        </div>
        
        <div class="search-container">
            <i class="ti ti-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Buscar en el menú..." id="searchInput">
        </div>
    </header>

    <!-- Categories Navigation -->
    <nav class="category-nav" id="categoryNav">
        <div class="category-pill active" onclick="filterCategory('all', this)">Todo</div>
        @foreach($categories as $category)
            @if($category->products->isNotEmpty())
            <div class="category-pill" onclick="scrollToCategory('cat-{{ $category->id }}', this)">
                {{ $category->name }}
            </div>
            @endif
        @endforeach
    </nav>

    <!-- Menu Content -->
    <div class="container">
        @forelse($categories as $category)
            @if($category->products->isNotEmpty())
            <section id="cat-{{ $category->id }}" class="category-section" data-name="{{ strtolower($category->name) }}">
                <h2 class="category-title">{{ $category->name }}</h2>
                <div class="product-list">
                    @foreach($category->products as $product)
                    <article class="product-card">
                        <div class="product-info">
                            <div class="product-header">
                                <h3 class="product-name">{{ $product->name }}</h3>
                                <p class="product-desc">{{ $product->description }}</p>
                            </div>
                            <div class="product-footer">
                                <div class="product-price">${{ number_format($product->price, 2) }}</div>
                            </div>
                        </div>
                        @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-image" loading="lazy">
                        @else
                        <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:#ccc;">
                            <i class="ti ti-photo-off"></i>
                        </div>
                        @endif
                    </article>
                    @endforeach
                </div>
            </section>
            @endif
        @empty
            <div class="empty-state">
                <i class="ti ti-cup empty-icon"></i>
                <h3>Menú en preparación</h3>
                <p>Pronto agregaremos nuestros deliciosos platillos.</p>
            </div>
        @endforelse
    </div>

    <script>
        // Optimize Selectors
        const searchInput = document.getElementById('searchInput');
        const productCards = document.querySelectorAll('.product-card');
        const categorySections = document.querySelectorAll('.category-section');
        const navPills = document.querySelectorAll('.category-pill');

        // Debounce Search
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const term = e.target.value.toLowerCase().trim();
                
                requestAnimationFrame(() => {
                    productCards.forEach(card => {
                        const name = card.querySelector('.product-name').textContent.toLowerCase();
                        const desc = card.querySelector('.product-desc').textContent.toLowerCase();
                        const isVisible = name.includes(term) || desc.includes(term);
                        card.style.display = isVisible ? 'flex' : 'none';
                    });

                    categorySections.forEach(sec => {
                        const hasVisibleProducts = sec.querySelector('.product-card[style="display: flex;"]');
                        sec.style.display = hasVisibleProducts ? 'block' : 'none';
                    });
                });
            }, 150); // 150ms delay
        });

        // Smooth Scroll to Category
        function scrollToCategory(id, element) {
            navPills.forEach(p => p.classList.remove('active'));
            element.classList.add('active');
            
            const section = document.getElementById(id);
            if(section) {
                const headerOffset = 130; 
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
            
                window.scrollTo({
                    top: offsetPosition,
                    behavior: "smooth"
                });
            }
        }

        function filterCategory(type, element) {
            navPills.forEach(p => p.classList.remove('active'));
            element.classList.add('active');
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        // Intersection Observer for Scroll Spy (High Performance)
        const observerOptions = {
            root: null,
            rootMargin: '-140px 0px -70% 0px', // Adjust active area
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    
                    navPills.forEach(p => {
                        p.classList.remove('active');
                        // Match onclick attribute content
                        if(p.getAttribute('onclick').includes(id)) {
                            p.classList.add('active');
                            // REMOVED: p.scrollIntoView(...) to prevent jitter/lag while scrolling
                        }
                    });
                }
            });
        }, observerOptions);

        categorySections.forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>
