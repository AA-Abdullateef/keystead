<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PruneIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:prune';

    protected $description = 'Delete stored idempotency keys older than the configured retention window';

    public function handle(): int
    {
        $n = IdempotencyKey::where('created_at', '<', now()->subHours((int) config('keystead.idempotency_ttl_hours', 24)))->delete();
        $this->info("Pruned {$n} idempotency key(s).");

        return self::SUCCESS;
    }
}
