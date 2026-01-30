<!doctype html>

<html
  lang="en"
  class=" layout-navbar-fixed layout-menu-fixed layout-compact "
  dir="ltr"
  data-skin="default"
  data-bs-theme="dark"
  data-assets-path="{{ asset('assets') }}/"
  data-template="vertical-menu-template-starter">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>@yield('title', 'Gestional Food')</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/icon_gestionalfood.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />

    <script src="{{ asset('assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ asset('assets/js/config.js') }}"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar  ">
      <div class="layout-container">
        <!-- Menu -->

        @if(session()->has('impersonator_id'))
        <div class="bg-danger text-white text-center py-2 fixed-top" style="z-index: 2000;">
            <i class="ti tabler-spy me-2"></i> Estás navegando como <strong>{{ auth()->user()->name }}</strong>. 
            <a href="{{ route('super-admin.stop-impersonating') }}" class="btn btn-sm btn-light text-danger fw-bold ms-2">
                <i class="ti tabler-logout me-1"></i> Volver a Super Admin
            </a>
        </div>
        <style>.layout-navbar { top: 40px !important; } .layout-menu { padding-top: 40px !important; }</style>
        @endif

        <aside id="layout-menu" class="layout-menu menu-vertical menu">
          <div class="app-brand demo ">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/icon_gestionalfood.png') }}" alt="Logo" width="32" height="32">
              </span>
              <span class="app-brand-text demo menu-text fw-bold ms-3" style="font-size: 1.2rem;">Gestional Food</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
              <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
              <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
          </div>

          <!-- Branch Switcher (Sidebar) -->
          @if(session()->has('branch_id'))
          <div class="px-3 mb-2">
              <div class="dropdown">
                <button class="btn btn-label-secondary btn-sm w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-container="body" data-bs-boundary="viewport">
                  <span class="d-flex align-items-center gap-2 text-truncate">
                      <i class="ti tabler-building-store"></i>
                      <span class="text-truncate" style="max-width: 140px;">{{ session('branch_name') }}</span>
                  </span>
                  <i class="ti tabler-chevron-down text-muted" style="font-size: 0.8rem;"></i>
                </button>
                <ul class="dropdown-menu w-100">
                    <li class="dropdown-header pt-0 pb-1 text-uppercase small">Cambiar Sucursal</li>
                    @foreach(auth()->user()->active_branches as $branch)
                    <li>
                         <form action="{{ route('branches.switch', $branch->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center {{ session('branch_id') == $branch->id ? 'active' : '' }}">
                                <span class="text-truncate">{{ $branch->name }}</span>
                                @if(session('branch_id') == $branch->id)
                                <i class="ti tabler-check text-primary"></i>
                                @endif
                            </button>
                        </form>
                    </li>
                    @endforeach
                    @if(auth()->user()->hasRole('administrador'))
                     <li><div class="dropdown-divider"></div></li>
                     <li><a href="{{ route('branches.index') }}" class="dropdown-item"><i class="ti tabler-settings me-1"></i> Administrar</a></li>
                    @endif
                </ul>
              </div>
          </div>
          @endif
          <!-- / Branch Switcher -->
          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            
            @if(auth()->check() && auth()->user()->hasRole('super_admin'))
                <!-- Super Admin Menu -->
                <li class="menu-item {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('super-admin.dashboard') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-dashboard"></i>
                        <div data-i18n="Dashboard">Dashboard</div>
                    </a>
                </li>
                
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Plataforma</span>
                </li>
                
                <li class="menu-item {{ request()->routeIs('super-admin.tenants') ? 'active' : '' }}">
                    <a href="{{ route('super-admin.tenants') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-users-group"></i>
                        <div data-i18n="Tenants">Tenants</div>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('super_admin.ai_queries.*') ? 'active' : '' }}">
                    <a href="{{ route('super_admin.ai_queries.index') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-robot"></i>
                        <div data-i18n="Consultas IA">Consultas IA</div>
                    </a>
                </li>
            @else
                <!-- Regular User Menu -->
            @unless(auth()->user()->hasRole('cocinero') || auth()->user()->hasRole('mesero'))
            <!-- Page -->
            <li class="menu-item {{ request()->is('/') ? 'active' : '' }}">
              <a href="{{ url('/') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-smart-home"></i>
                <div data-i18n="Dashboard">Dashboard</div>
              </a>
            </li>

            <!-- Catálogo -->
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Catálogo</span>
            </li>
            
            <li class="menu-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
              <a href="{{ route('products.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-box"></i>
                <div data-i18n="Productos">Productos</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
              <a href="{{ route('categories.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-category"></i>
                <div data-i18n="Categorías">Categorías</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('preparation-areas.*') ? 'active' : '' }}">
              <a href="{{ route('preparation-areas.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-cooker"></i>
                <div data-i18n="Áreas">Áreas de Prep.</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
              <a href="{{ route('expense-categories.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-tags"></i>
                <div data-i18n="Cat. Gastos">Cat. Gastos</div>
              </a>
            </li>
            @endunless

            <li class="menu-item {{ request()->routeIs('tables.*') ? 'active' : '' }}">
              <a href="{{ route('tables.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-table"></i>
                <div data-i18n="Mesas">Mesas</div>
              </a>
            </li>

            @unless(auth()->user()->hasRole('mesero'))
            <li class="menu-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
              <a href="{{ route('orders.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-clipboard-list"></i>
                <div data-i18n="Comandas">Comandas</div>
              </a>
            </li>
            @endunless
            <!-- End of Access Control -->

            @unless(auth()->user()->hasRole('mesero'))
            <!-- Monitores -->
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Monitores</span>
            </li>
            
            <li class="menu-item {{ request()->routeIs('kitchen.*') ? 'active' : '' }}">
              <a href="{{ route('kitchen.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-device-desktop-analytics"></i>
                <div data-i18n="Pantalla de Cocina">Pantalla de Cocina</div>
              </a>
            </li>
            @endunless

            @unless(auth()->user()->hasRole('cocinero') || auth()->user()->hasRole('mesero'))
            <!-- Configuración -->
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Administración</span>
            </li>

            <li class="menu-item {{ request()->routeIs('cash-registers.*') ? 'active' : '' }}">
              <a href="{{ route('cash-registers.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-cash"></i>
                <div data-i18n="Cortes de Caja">Cortes de Caja</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('ai-reports.*') ? 'active' : '' }}">
              <a href="{{ route('ai-reports.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-chart-bar"></i>
                <div data-i18n="Reportes IA">Reportes IA</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
              <a href="{{ route('users.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-users"></i>
                <div data-i18n="Usuarios">Usuarios</div>
              </a>
            </li>

            <li class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
              <a href="{{ route('settings.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-settings"></i>
                <div data-i18n="Configuración">Configuración</div>
              </a>
            </li>

            @if(auth()->user()->hasRole('administrador'))
            <li class="menu-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
              <a href="{{ route('branches.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-building-store"></i>
                <div data-i18n="Sucursales">Sucursales</div>
              </a>
            </li>

            <!-- Reportes -->
            <li class="menu-item {{ request()->routeIs('reports.*') ? 'active open' : '' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-report-analytics"></i>
                <div data-i18n="Reportes">Reportes</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('reports.sales.index') ? 'active' : '' }}">
                  <a href="{{ route('reports.sales.index') }}" class="menu-link">
                    <div data-i18n="Ventas">Ventas</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('ai-reports.index') ? 'active' : '' }}">
                  <a href="{{ route('ai-reports.index') }}" class="menu-link">
                    <div data-i18n="Reportes IA">Reportes IA</div>
                  </a>
                </li>
              </ul>
            </li>
            @endif
            @endunless
            @endif

             <!-- User Info & Theme (Bottom of Menu) -->
             <li class="menu-item mt-auto">
                 <div class="menu-link user-profile-link justify-content-between align-items-center p-2 rounded mx-2 mb-2 bg-label-secondary">
                     <div class="d-flex align-items-center overflow-hidden">
                         <div class="avatar avatar-sm me-2 flex-shrink-0">
                             <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                         </div>
                         <div class="d-flex flex-column text-truncate">
                             <span class="fw-bold text-truncate" style="max-width: 90px; font-size: 0.85rem;">{{ auth()->user()->name }}</span>
                             <span class="text-muted small text-truncate" style="max-width: 90px; font-size: 0.75rem;">{{ auth()->user()->roles->pluck('name')->implode(', ') }}</span>
                         </div>
                     </div>
                     <div class="d-flex align-items-center gap-1">
                        <!-- Theme Toggle -->
                        <div class="dropdown dropup">
                            <button class="btn btn-icon btn-sm btn-text-secondary rounded-pill" type="button" id="nav-theme" data-bs-toggle="dropdown" aria-expanded="false" data-bs-container="body" data-bs-boundary="viewport">
                                <i class="ti tabler-palette icon-md theme-icon-active"></i>
                                <span id="nav-theme-text" class="d-none">Tema</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="nav-theme">
                                <li>
                                  <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="light">
                                    <i class="ti tabler-sun me-2"></i>Claro
                                  </button>
                                </li>
                                <li>
                                  <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark">
                                    <i class="ti tabler-moon me-2"></i>Oscuro
                                  </button>
                                </li>
                                <li>
                                  <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system">
                                    <i class="ti tabler-device-desktop me-2"></i>Sistema
                                  </button>
                                </li>
                            </ul>
                        </div>
                         
                        <a href="javascript:void(0);" onclick="document.getElementById('logout-form').submit();" class="btn btn-icon btn-sm btn-text-danger rounded-pill" title="Cerrar Sesión">
                            <i class="ti tabler-power icon-md"></i>
                        </a>
                     </div>
                 </div>
             </li>
        </aside>

        <div class="menu-mobile-toggler d-xl-none rounded-1">
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
            <i class="ti tabler-menu icon-base"></i>
            <i class="ti tabler-chevron-right icon-base"></i>
          </a>
        </div>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0   d-xl-none ">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base ti tabler-menu-2 icon-md"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
               <div class="navbar-nav align-items-center">
                  <!-- Navbar Search or other left items if needed -->
               </div>

              <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online bg-label-primary">
                      <span class="avatar-initial rounded-circle">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online bg-label-primary">
                              <span class="avatar-initial rounded-circle">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                            <small class="text-body-secondary">{{ auth()->user()->roles->pluck('name')->implode(', ') ?? 'Usuario' }}</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>

                    <li>
                      <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('logout-form').submit();">
                        <i class="icon-base ti tabler-power icon-md me-3"></i><span>Cerrar Sesión</span>
                      </a>
                      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                          @csrf
                      </form>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
                @yield('content')
            </div>
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                  <div class="text-body">
                    &#169;
                    <script>
                      document.write(new Date().getFullYear());
                    </script>
                    , made with ❤️ by <a href="https://pixinvent.com" target="_blank" class="footer-link">Gestional Food</a>
                  </div>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js  -->

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>

    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->

    <script src="{{ asset('assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <!-- Page JS -->
    @stack('scripts')
  </body>
</html>
