<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        // Get all unique tenants
        $tenants = User::select('tenant_id')
            ->distinct()
            ->whereNotNull('tenant_id')
            ->get()
            ->pluck('tenant_id');

        $totalTenants = $tenants->count();

        // Total orders across all tenants
        $totalOrders = Order::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->count();

        // Total sales across all tenants
        $totalSales = Payment::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->sum('amount');

        // Active users in last 24 hours
        $activeUsers = User::where('updated_at', '>=', now()->subDay())->count();

        // New tenants this month
        $newTenantsThisMonth = User::select('tenant_id')
            ->distinct()
            ->whereNotNull('tenant_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Top tenants by sales
        $topTenants = Payment::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)
            ->select('payments.tenant_id', DB::raw('SUM(payments.amount) as total_sales'))
            ->groupBy('payments.tenant_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        // Recent activity - last 20 orders
        $recentOrders = Order::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)
            ->with(['user', 'branch'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Tenants list with basic info
        $tenantsData = [];
        foreach ($tenants as $tenantId) {
            $users = User::where('tenant_id', $tenantId)->count();
            $orders = Order::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('tenant_id', $tenantId)->count();
            $sales = Payment::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('tenant_id', $tenantId)->sum('amount');
            $lastActivity = Order::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('tenant_id', $tenantId)->max('created_at');

            $tenantsData[] = [
                'tenant_id' => $tenantId,
                'users' => $users,
                'orders' => $orders,
                'sales' => $sales,
                'last_activity' => $lastActivity,
            ];
        }

        // Sort by sales
        usort($tenantsData, function($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });

        return view('super_admin.dashboard', compact(
            'totalTenants',
            'totalOrders',
            'totalSales',
            'activeUsers',
            'newTenantsThisMonth',
            'topTenants',
            'recentOrders',
            'tenantsData'
        ));
    }

    public function tenants()
    {
        $tenants = User::select('tenant_id')
            ->distinct()
            ->whereNotNull('tenant_id')
            ->get();

        $tenantsData = [];
        
        foreach ($tenants as $t) {
            // Get the main admin user for this tenant
            // We look for a user with 'administrador' role in this tenant
            $adminUser = User::where('tenant_id', $t->tenant_id)
                ->whereHas('roles', function($q) {
                    $q->where('name', 'administrador');
                })
                ->first();

            // If no admin found, take the first user (likely the owner)
            if (!$adminUser) {
                $adminUser = User::where('tenant_id', $t->tenant_id)->first();
            }

            if (!$adminUser) continue;

            // Get a branch name (any branch from this tenant)
            $branchName = 'N/A';
            if ($adminUser->tenant_id) {
                $branch = \App\Models\Branch::where('tenant_id', $adminUser->tenant_id)->first();
                if ($branch) {
                    $branchName = $branch->name;
                }
            }

            $usersCount = User::where('tenant_id', $t->tenant_id)->count();
            $ordersCount = Order::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('tenant_id', $t->tenant_id)->count();
            $salesTotal = Payment::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('tenant_id', $t->tenant_id)->sum('amount');
            
            $tenantsData[] = [
                'tenant_id' => $t->tenant_id,
                'branch_name' => $branchName,
                'admin_user' => $adminUser,
                'users_count' => $usersCount,
                'orders_count' => $ordersCount,
                'sales_total' => $salesTotal,
                'created_at' => $adminUser->created_at,
            ];
        }

        return view('super_admin.tenants', compact('tenantsData'));
    }

    public function impersonate(User $user)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        // Store original user id in session
        session()->put('impersonator_id', auth()->id());

        // Login as the user
        auth()->login($user);

        // Redirect to branch selection to simulate normal login flow
        return redirect()->route('branches.select')->with('success', "Has iniciado sesión como {$user->name}");
    }

    public function stopImpersonating()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('dashboard');
        }

        // Login back as super admin
        auth()->loginUsingId(session('impersonator_id'));
        
        // Clear session
        session()->forget('impersonator_id');

        return redirect()->route('super-admin.dashboard')->with('success', 'Bienvenido de vuelta, Super Admin');
    }

    public function createSubscription()
    {
        return view('super_admin.subscriptions.create');
    }

    public function storeSubscription(Request $request, \App\Services\SubscriptionService $subscriptionService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'country_code' => 'required|string',
            'whatsapp_number' => 'required|string',
        ]);

        $data['pais_whatsapp'] = '+' . $data['country_code'] . ' ' . $data['whatsapp_number'];

        $result = $subscriptionService->createSubscriptionWithInvitation($data);

        return view('super_admin.subscriptions.success', [
            'link' => $result['link'],
            'email' => $result['user']->email,
            'name' => $result['user']->name
        ]);
    }
}
