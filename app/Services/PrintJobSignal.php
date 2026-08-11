<?php

namespace App\Services;

use App\Models\PrintAgent;
use Illuminate\Support\Facades\Redis;

class PrintJobSignal
{
    public function resolveAgent(string $tokenHash): ?PrintAgent
    {
        if (! $this->usesRedis()) {
            return $this->findAgent($tokenHash);
        }

        try {
            $redis = Redis::connection(config('printing.redis_connection'));
            $cached = $redis->get($this->authKey($tokenHash));

            if ($cached === 'missing') {
                return null;
            }

            if ($cached) {
                return (new PrintAgent)->newFromBuilder(json_decode($cached, true));
            }

            $agent = $this->findAgent($tokenHash);
            if ($agent) {
                $this->rememberAgent($agent);
            } else {
                $redis->setex($this->authKey($tokenHash), 60, 'missing');
            }

            return $agent;
        } catch (\Throwable) {
            return $this->findAgent($tokenHash);
        }
    }

    public function rememberAgent(PrintAgent $agent): void
    {
        if (! $this->usesRedis()) {
            return;
        }

        try {
            Redis::connection(config('printing.redis_connection'))->setex(
                $this->authKey($agent->token_hash),
                300,
                json_encode($agent->getAttributes(), JSON_THROW_ON_ERROR)
            );
        } catch (\Throwable) {
        }
    }

    public function forgetAgent(?string $tokenHash): void
    {
        if (! $this->usesRedis() || ! $tokenHash) {
            return;
        }

        try {
            Redis::connection(config('printing.redis_connection'))->del($this->authKey($tokenHash));
        } catch (\Throwable) {
        }
    }

    public function notify(PrintAgent $agent): void
    {
        if (! $this->usesRedis()) {
            return;
        }

        try {
            $redis = Redis::connection(config('printing.redis_connection'));
            $key = $this->key($agent);

            if ((int) $redis->llen($key) === 0) {
                $redis->rpush($key, '1');
            }

            $redis->expire($key, 3600);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function consume(PrintAgent $agent): bool
    {
        if (! $this->usesRedis()) {
            return true;
        }

        try {
            return Redis::connection(config('printing.redis_connection'))
                ->lpop($this->key($agent)) !== null;
        } catch (\Throwable) {
            return true;
        }
    }

    private function usesRedis(): bool
    {
        return config('printing.signal_driver') === 'redis';
    }

    private function key(PrintAgent $agent): string
    {
        return 'print-agent:'.$agent->id.':signals';
    }

    private function authKey(string $tokenHash): string
    {
        return 'print-agent-auth:'.$tokenHash;
    }

    private function findAgent(string $tokenHash): ?PrintAgent
    {
        return PrintAgent::withoutGlobalScopes()
            ->where('token_hash', $tokenHash)
            ->first();
    }
}
