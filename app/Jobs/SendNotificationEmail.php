<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Services\SendGridMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /** Window (seconds) during which an identical email is treated as a duplicate. */
    private const DEDUP_TTL = 300; // 5 minutes

    public function __construct(
        public string $to,
        public string $subject,
        public string $htmlBody,
        public ?string $type = null,
    ) {}

    public function handle(SendGridMailService $mailService): void
    {
        $dedupKey = $this->dedupKey();

        // 1. Skip cleanly if SendGrid isn't configured (no point retrying a config issue).
        $apiKey = config('services.sendgrid.api_key');
        if (empty($apiKey) || $apiKey === 'your-sendgrid-api-key-here') {
            $this->record(EmailLog::STATUS_SKIPPED, $dedupKey, 'SendGrid API key not configured');
            Log::channel('email')->warning("SKIPPED (not configured) to={$this->to} subject=\"{$this->subject}\"");

            return;
        }

        // 2. Redis duplicate guard: atomically claim this email for DEDUP_TTL seconds.
        //    Cache::add() is backed by the default cache store and returns false if already claimed.
        if (! Cache::add($dedupKey, now()->toIso8601String(), self::DEDUP_TTL)) {
            $this->record(EmailLog::STATUS_SKIPPED, $dedupKey, 'Duplicate suppressed (sent within '.self::DEDUP_TTL.'s)');
            Log::channel('email')->info("SKIPPED (duplicate) to={$this->to} subject=\"{$this->subject}\"");

            return;
        }

        // 3. Attempt delivery.
        $ok = $mailService->send($this->to, $this->subject, $this->htmlBody);

        if ($ok) {
            $this->record(EmailLog::STATUS_SENT, $dedupKey);
            Log::channel('email')->info("SENT to={$this->to} subject=\"{$this->subject}\" type={$this->type}");

            return;
        }

        // 4. Failure: release the dedup lock so the retry can try again, then throw to retry.
        Cache::forget($dedupKey);
        $this->record(EmailLog::STATUS_FAILED, $dedupKey, 'SendGrid delivery failed (see app log)');
        Log::channel('email')->error("FAILED to={$this->to} subject=\"{$this->subject}\" attempt={$this->attempts()}");

        throw new \RuntimeException("Email delivery to {$this->to} failed");
    }

    /** Called once all retries are exhausted. */
    public function failed(?Throwable $e): void
    {
        // Make sure a stale lock never blocks a future send.
        Cache::forget($this->dedupKey());
        Log::channel('email')->error("GAVE UP to={$this->to} subject=\"{$this->subject}\" reason=".($e?->getMessage() ?? 'unknown'));
    }

    private function dedupKey(): string
    {
        return 'email_dedup:'.md5($this->to.'|'.$this->subject.'|'.$this->htmlBody);
    }

    private function record(string $status, string $dedupKey, ?string $error = null): void
    {
        EmailLog::create([
            'to_email' => $this->to,
            'subject' => $this->subject,
            'type' => $this->type,
            'status' => $status,
            'error' => $error,
            'dedup_key' => substr($dedupKey, strlen('email_dedup:')),
            'attempt' => $this->attempts(),
        ]);
    }
}
