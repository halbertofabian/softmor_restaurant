<?php

namespace App\Http\Controllers;

use App\Models\AiReport;
use App\Services\AiReportService;
use Illuminate\Http\Request;

class AiReportController extends Controller
{
    protected $aiService;

    public function __construct(AiReportService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $reports = AiReport::where('branch_id', session('branch_id'))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $favorites = AiReport::where('branch_id', session('branch_id'))
            ->where('is_favorite', true)
            ->latest()
            ->take(5)
            ->get();

        return view('ai_reports.index', compact('reports', 'favorites'));
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        try {
            // Process question with AI
            $result = $this->aiService->processQuestion(
                $request->question,
                session('branch_id')
            );

            // Save report
            // Save successful report
            $report = AiReport::create([
                'question' => $request->question,
                'interpretation' => $result['interpretation'],
                'sql_query' => $result['sql'],
                'parameters' => $result['parameters'],
                'result_data' => $result['results'],
                'chart_type' => $result['chart_type'],
                'chart_config' => $result['chart_config'],
                'status' => 'success',
            ]);

            return response()->json([
                'success' => true,
                'report' => $report,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            // Save failed report
            AiReport::create([
                'question' => $request->question,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'sql_query' => $result['sql'] ?? null, // Attempt to capture SQL if available from a previous step, but variable scope prevents this.
                // Note: To capture SQL on error, we would need to refactor to get SQL before execution. 
                // For now, we capture the error message and question.
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(AiReport $aiReport)
    {
        return view('ai_reports.show', compact('aiReport'));
    }

    public function toggleFavorite(AiReport $aiReport)
    {
        $aiReport->update([
            'is_favorite' => !$aiReport->is_favorite
        ]);

        return back()->with('success', $aiReport->is_favorite ? 'Agregado a favoritos' : 'Removido de favoritos');
    }

    public function destroy(AiReport $aiReport)
    {
        $aiReport->delete();
        return redirect()->route('ai-reports.index')->with('success', 'Reporte eliminado');
    }
}
