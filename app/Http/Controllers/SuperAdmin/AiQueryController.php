<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiReport;
use App\Models\Scopes\BranchScope;
use Illuminate\Http\Request;

class AiQueryController extends Controller
{
    public function index(Request $request)
    {
        $query = AiReport::withoutGlobalScope(BranchScope::class)
            ->with(['user', 'branch'])
            ->latest();

        if ($request->has('status') && in_array($request->status, ['success', 'error'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('sql_query', 'like', "%{$search}%")
                  ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(20);

        return view('super_admin.ai_queries.index', compact('reports'));
    }
}
