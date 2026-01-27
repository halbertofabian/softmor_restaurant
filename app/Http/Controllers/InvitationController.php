<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function showSetupForm($token)
    {
        $invitation = DB::table('setup_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            abort(404, 'El enlace de invitación es inválido o ha expirado.');
        }

        $user = User::where('email', $invitation->email)->firstOrFail();

        return view('auth.setup-account', compact('token', 'user'));
    }

    public function setupAccount(Request $request, $token)
    {
        $invitation = DB::table('setup_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return back()->with('error', 'El enlace ha expirado o no es válido.');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $invitation->email)->firstOrFail();

        // Update User
        $user->update([
            'password' => Hash::make($request->password),
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);

        // Delete used token
        DB::table('setup_tokens')->where('token', $token)->delete();

        // Login user
        Auth::login($user);

        // Check if user has active branches
        if ($user->active_branches->isEmpty()) {
            return redirect()->route('setup-branch.create');
        }

        return redirect()->route('branches.select');
    }

    public function createBranch()
    {
        return view('auth.setup-branch');
    }

    public function storeBranch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Create Branch
        $branch = \App\Models\Branch::create([
            'tenant_id' => $user->tenant_id,
            'name' => $request->name,
            'address' => $request->address,
            'is_active' => true
        ]);

        // Assign User to Branch
        $user->branches()->attach($branch->id, [
            'tenant_id' => $user->tenant_id, 
            'is_active' => true
        ]);

        // Auto select this branch
        session(['branch_id' => $branch->id]);
        session(['branch_name' => $branch->name]);

        return redirect()->route('dashboard');
    }
}
