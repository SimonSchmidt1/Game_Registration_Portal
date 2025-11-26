<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PasswordResetToken;

class PruneExpiredPasswordResetTokens extends Command
{
    protected $signature = 'tokens:prune {--days=7 : Remove used tokens older than X days (default 7)}';
    protected $description = 'Delete expired or used password/temporary access tokens';

    public function handle(): int
    {
        $now = now();
        $days = (int)$this->option('days');
        $thresholdUsed = $now->copy()->subDays($days);

        // Expired (past expires_at) and unused tokens
        $expired = PasswordResetToken::where('expires_at', '<', $now)->where('used', false)->delete();

        // Used tokens older than threshold
        $oldUsed = PasswordResetToken::where('used', true)->where('updated_at', '<', $thresholdUsed)->delete();

        $this->info("Pruned {$expired} expired + {$oldUsed} old used tokens.");
        return Command::SUCCESS;
    }
}
