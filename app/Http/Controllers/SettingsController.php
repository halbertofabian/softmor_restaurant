<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        // Fetch all settings for the current branch (or default if not branch context)
        // For now assuming session('branch_id') is set or null
        $topBranchId = session('branch_id'); // We might want to allow null for global settings?
        
        $settings = Setting::where('branch_id', $topBranchId)->pluck('value', 'key')->toArray();
        
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');
        $branchId = session('branch_id');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key, 'branch_id' => $branchId],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
