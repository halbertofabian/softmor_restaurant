<?php

use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\AiQueryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QrMenuController;

use App\Http\Controllers\Auth\AuthController;

// Guest Routes
Route::middleware('guest')->group(function () {
    
// Public QR Menu Route
Route::get('/menu/{tenantId}/{branchId}', [QrMenuController::class, 'index'])->name('menu.public');

Route::get('/', function () {
        return view('welcome');
    })->name('welcome');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Account Setup (Invitation) - Public but validated by token
Route::get('/setup-account/{token}', [\App\Http\Controllers\InvitationController::class, 'showSetupForm'])->name('setup-account.show');
Route::post('/setup-account/{token}', [\App\Http\Controllers\InvitationController::class, 'setupAccount'])->name('setup-account.store');

// Setup First Branch - Auth required, NO branch middleware
Route::middleware('auth')->get('/setup-branch', [\App\Http\Controllers\InvitationController::class, 'createBranch'])->name('setup-branch.create');
Route::middleware('auth')->post('/setup-branch', [\App\Http\Controllers\InvitationController::class, 'storeBranch'])->name('setup-branch.store');

// Super Admin Routes
Route::middleware(['auth', \App\Http\Middleware\SuperAdminMiddleware::class])->prefix('super-admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('super-admin.dashboard');
    Route::get('/tenants', [\App\Http\Controllers\SuperAdminController::class, 'tenants'])->name('super-admin.tenants');
    Route::get('/impersonate/{user}', [\App\Http\Controllers\SuperAdminController::class, 'impersonate'])->name('super-admin.impersonate');
    
    // Subscription Routes
    Route::get('/subscriptions/create', [\App\Http\Controllers\SuperAdminController::class, 'createSubscription'])->name('super-admin.subscriptions.create');
    Route::post('/subscriptions', [\App\Http\Controllers\SuperAdminController::class, 'storeSubscription'])->name('super-admin.subscriptions.store');
    
    // AI Queries
    Route::name('super_admin.')->group(function () {
        Route::resource('ai_queries', AiQueryController::class);
    });
});

// Route to stop impersonating (must be accessible by the impersonated user)
Route::middleware('auth')->get('/super-admin/stop-impersonating', [\App\Http\Controllers\SuperAdminController::class, 'stopImpersonating'])->name('super-admin.stop-impersonating');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Branch Selection Routes (Auth only)
    Route::get('/select-branch', [\App\Http\Controllers\BranchSessionController::class, 'select'])->name('branches.select');
    Route::post('/select-branch', [\App\Http\Controllers\BranchSessionController::class, 'start'])->name('branches.start');
    Route::post('/switch-branch/{branch}', [\App\Http\Controllers\BranchSessionController::class, 'switch'])->name('branches.switch');

    // Branch Protected Routes
    Route::middleware('branch')->group(function () {
        
        // --- Management & Catalog Routes (Admin, Caja) ---
        // Excludes Mesero and Cocinero
        Route::middleware(['role:administrador,caja'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
            
            // Users (Scoped by Tenant)
            Route::resource('users', \App\Http\Controllers\UserController::class);

            Route::resource('preparation-areas', \App\Http\Controllers\PreparationAreaController::class);
            Route::resource('categories', \App\Http\Controllers\CategoryController::class);
            Route::resource('products', \App\Http\Controllers\ProductController::class);

            // POS
            Route::middleware(['cash.register'])->group(function () {
                Route::get('pos/{order}/checkout', [\App\Http\Controllers\PosController::class, 'show'])->name('pos.checkout');
                Route::post('pos/{order}/pay', [\App\Http\Controllers\PosController::class, 'pay'])->name('pos.pay');
                Route::post('pos/{order}/send-to-kitchen', [\App\Http\Controllers\PosController::class, 'sendToKitchen'])->name('pos.send-to-kitchen');
                Route::get('pos/{order}/ticket', [\App\Http\Controllers\PosController::class, 'ticket'])->name('pos.ticket');
                Route::get('pos/{order}/ticket/pdf', [\App\Http\Controllers\PosController::class, 'ticketPdf'])->name('pos.ticket.pdf');
                Route::get('pos/{order}/print', [\App\Http\Controllers\PosController::class, 'printDirect'])->name('pos.print');
            });


            
            // Cash Registers
            Route::resource('cash-registers', \App\Http\Controllers\CashRegisterController::class);
            Route::post('cash-registers/{cash_register}/movements', [\App\Http\Controllers\CashRegisterController::class, 'storeMovement'])->name('cash-registers.movements.store');
            Route::get('cash-registers/{cash_register}/print', [\App\Http\Controllers\CashRegisterController::class, 'print'])->name('cash-registers.print');
            Route::get('cash-registers/{cash_register}/report', [\App\Http\Controllers\CashRegisterController::class, 'report'])->name('cash-registers.report');
            
            Route::resource('expense-categories', \App\Http\Controllers\ExpenseCategoryController::class)->only(['index', 'store', 'destroy']);
            
            // AI Reports
            Route::get('ai-reports', [\App\Http\Controllers\AiReportController::class, 'index'])->name('ai-reports.index');
            Route::post('ai-reports/ask', [\App\Http\Controllers\AiReportController::class, 'ask'])->name('ai-reports.ask');
            Route::get('ai-reports/{aiReport}', [\App\Http\Controllers\AiReportController::class, 'show'])->name('ai-reports.show');
            Route::post('ai-reports/{aiReport}/favorite', [\App\Http\Controllers\AiReportController::class, 'toggleFavorite'])->name('ai-reports.favorite');
            Route::delete('ai-reports/{aiReport}', [\App\Http\Controllers\AiReportController::class, 'destroy'])->name('ai-reports.destroy');
            
            Route::get('settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

            // Sales Report
            Route::get('reports/sales', [\App\Http\Controllers\SalesReportController::class, 'index'])->name('reports.sales.index');
        });

        // --- Operations Routes (Admin, Mesero, Caja) ---
        Route::middleware(['role:administrador,mesero,caja'])->group(function () {
            // Tables
            Route::resource('tables', \App\Http\Controllers\TableController::class);
            Route::put('tables/{table}/occupy', [\App\Http\Controllers\TableController::class, 'occupy'])->name('tables.occupy');
            Route::put('tables/{table}/release', [\App\Http\Controllers\TableController::class, 'release'])->name('tables.release');

            // Mobile Order View (No cash register required for waiters)
            Route::get('orders/{order}/mobile', [\App\Http\Controllers\OrderController::class, 'mobile'])->name('orders.mobile');
            Route::post('orders/{order}/add-item', [\App\Http\Controllers\OrderController::class, 'addItem'])->name('orders.add-item');
            Route::delete('orders/{order}/details/{detail}', [\App\Http\Controllers\OrderController::class, 'removeItem'])->name('orders.remove-item');
            Route::post('orders/{order}/send', [\App\Http\Controllers\OrderController::class, 'sendToKitchen'])->name('orders.send');

            // Orders (Cash register required)
            Route::middleware(['cash.register'])->group(function () {
                Route::resource('orders', \App\Http\Controllers\OrderController::class)->except(['create', 'edit']);
                Route::get('orders/{order}/pre-check', [\App\Http\Controllers\PosController::class, 'preCheck'])->name('orders.pre-check');
                Route::put('orders/{order}/close', [\App\Http\Controllers\OrderController::class, 'close'])->name('orders.close');
            });
        });

        Route::middleware(['role:administrador'])->group(function () {
             Route::resource('branches', \App\Http\Controllers\BranchController::class);
             
             // QR Codes
             Route::get('branches/{branch}/qr', [\App\Http\Controllers\QrMenuController::class, 'generate'])->name('branches.qr');
             Route::get('branches/{branch}/qr/download', [\App\Http\Controllers\QrMenuController::class, 'download'])->name('branches.qr.download');
        });

        // --- Kitchen Routes (Cocinero, Admin) ---
        Route::middleware(['role:cocinero,administrador'])->group(function () {
            Route::get('kitchen', [\App\Http\Controllers\KitchenController::class, 'index'])->name('kitchen.index');
            Route::get('kitchen/{area}', [\App\Http\Controllers\KitchenController::class, 'monitor'])->name('kitchen.monitor');
            Route::get('kitchen/{area}/check-new', [\App\Http\Controllers\KitchenController::class, 'checkNewItems'])->name('kitchen.check-new');
            Route::post('kitchen/mark-printed', [\App\Http\Controllers\KitchenController::class, 'markAsPrinted'])->name('kitchen.mark-printed');
        });
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
