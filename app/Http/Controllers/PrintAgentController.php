<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Services\PrintJobSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrintAgentController extends Controller
{
    public function __construct(private PrintJobSignal $signal) {}

    public function connect(Request $request)
    {
        $branchId = session('branch_id');
        $token = Str::random(64);
        $existingAgent = PrintAgent::withoutGlobalScopes()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('branch_id', $branchId)
            ->first();

        $agent = PrintAgent::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $request->user()->tenant_id, 'branch_id' => $branchId],
            ['token_hash' => hash('sha256', $token), 'last_seen_at' => null]
        );
        $this->signal->forgetAgent($existingAgent?->token_hash);
        $this->signal->rememberAgent($agent);

        return response()->json([
            'server_url' => url('/api/print-agent'),
            'token' => $token,
        ]);
    }

    public function next(Request $request)
    {
        $agent = $this->agent($request);
        if (! $agent) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->touch($agent);

        if (! $request->boolean('force') && ! $this->signal->consume($agent)) {
            return response()->noContent();
        }

        $job = DB::transaction(function () use ($agent) {
            PrintJob::where('tenant_id', $agent->tenant_id)
                ->where('branch_id', $agent->branch_id)
                ->where('status', 'processing')
                ->where('locked_at', '<', now()->subMinutes(5))
                ->update(['status' => 'pending', 'locked_at' => null]);

            $job = PrintJob::where('tenant_id', $agent->tenant_id)
                ->where('branch_id', $agent->branch_id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($job) {
                $job->update([
                    'status' => 'processing',
                    'locked_at' => now(),
                    'attempts' => $job->attempts + 1,
                    'error' => null,
                ]);
            }

            return $job;
        });

        if (!$job) {
            return response()->noContent();
        }

        return response()->json(['id' => $job->id, 'payload' => $job->payload]);
    }

    public function config(Request $request)
    {
        $agent = $this->agent($request);
        if (!$agent) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->touch($agent);

        return response()->json([
            'poll_interval_ms' => config('printing.poll_interval_ms'),
            'recovery_interval_seconds' => config('printing.recovery_interval_seconds'),
        ]);
    }

    public function printed(Request $request, PrintJob $job)
    {
        $agent = $this->agent($request);
        if (!$agent || !$this->owns($job, $agent)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $job->update(['status' => 'printed', 'printed_at' => now(), 'locked_at' => null]);
        OrderDetail::withoutGlobalScopes()
            ->whereIn('id', $job->order_detail_ids ?? [])
            ->update(['is_printed' => true]);

        return response()->noContent();
    }

    public function failed(Request $request, PrintJob $job)
    {
        $agent = $this->agent($request);
        if (!$agent || !$this->owns($job, $agent)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $job->update([
            'status' => 'pending',
            'locked_at' => null,
            'error' => (string) $request->input('error', 'Print failed'),
        ]);
        $this->signal->notify($agent);

        return response()->noContent();
    }

    private function agent(Request $request): ?PrintAgent
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        return $this->signal->resolveAgent(hash('sha256', $token));
    }

    private function owns(PrintJob $job, PrintAgent $agent): bool
    {
        return $job->tenant_id === $agent->tenant_id && (int) $job->branch_id === (int) $agent->branch_id;
    }

    private function touch(PrintAgent $agent): void
    {
        if (! $agent->last_seen_at || $agent->last_seen_at->lt(now()->subSeconds(30))) {
            $agent->update(['last_seen_at' => now()]);
            $this->signal->rememberAgent($agent);
        }
    }
}
