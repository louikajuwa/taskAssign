<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneEmailLogs extends Command
{
    protected $signature = 'emails:prune-logs {--days=30 : Delete email logs older than this many days}';

    protected $description = 'Delete email delivery logs older than the retention window (default 30 days)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = EmailLog::where('created_at', '<', $cutoff)->delete();

        $message = "Pruned {$deleted} email log(s) older than {$days} days (before {$cutoff->toDateTimeString()}).";
        $this->info($message);
        Log::channel('email')->info("PRUNE {$message}");

        return self::SUCCESS;
    }
}
