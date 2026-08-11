<?php

return [
    'signal_driver' => env('PRINT_AGENT_SIGNAL_DRIVER', 'database'),
    'redis_connection' => env('PRINT_AGENT_REDIS_CONNECTION', 'default'),
    'poll_interval_ms' => (int) env('PRINT_AGENT_POLL_INTERVAL_MS', 3000),
    'recovery_interval_seconds' => (int) env('PRINT_AGENT_RECOVERY_INTERVAL_SECONDS', 60),
];
