<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationEmail;
use App\Models\EmailLog;
use App\Services\SendGridMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class SendNotificationEmailJobTest extends TestCase
{
    use RefreshDatabase;

    private function job(string $body = '<p>hi</p>'): SendNotificationEmail
    {
        return new SendNotificationEmail('to@example.com', 'Subject', $body, 'assignment_added');
    }

    public function test_dedup_sends_once_for_identical_payload(): void
    {
        config(['services.sendgrid.api_key' => 'SG.real']);
        $this->mock(SendGridMailService::class, function (MockInterface $m) {
            $m->shouldReceive('send')->once()->with('to@example.com', 'Subject', '<p>hi</p>')->andReturnTrue();
        });

        SendNotificationEmail::dispatch('to@example.com', 'Subject', '<p>hi</p>', 'assignment_added');
        SendNotificationEmail::dispatch('to@example.com', 'Subject', '<p>hi</p>', 'assignment_added');

        $this->assertSame(1, EmailLog::where('status', EmailLog::STATUS_SENT)->count());
        $this->assertSame(1, EmailLog::where('status', EmailLog::STATUS_SKIPPED)->count());
        $this->assertSame(2, EmailLog::count());
        $this->assertStringContainsString('Duplicate', EmailLog::where('status', 'skipped')->value('error'));
    }

    public function test_different_payloads_are_both_sent(): void
    {
        config(['services.sendgrid.api_key' => 'SG.real']);
        $this->mock(SendGridMailService::class, function (MockInterface $m) {
            $m->shouldReceive('send')->twice()->andReturnTrue();
        });

        SendNotificationEmail::dispatch('to@example.com', 'Subject', '<p>one</p>');
        SendNotificationEmail::dispatch('to@example.com', 'Subject', '<p>two</p>');

        $this->assertSame(2, EmailLog::where('status', 'sent')->count());
    }

    public function test_missing_api_key_logs_skipped_and_never_sends(): void
    {
        config(['services.sendgrid.api_key' => null]);
        $this->mock(SendGridMailService::class, function (MockInterface $m) {
            $m->shouldNotReceive('send');
        });

        SendNotificationEmail::dispatch('to@example.com', 'Subject', '<p>hi</p>');

        $log = EmailLog::sole();
        $this->assertSame(EmailLog::STATUS_SKIPPED, $log->status);
        $this->assertSame('to@example.com', $log->to_email);
        $this->assertStringContainsString('not configured', $log->error);
    }

    public function test_placeholder_api_key_is_treated_as_unconfigured(): void
    {
        config(['services.sendgrid.api_key' => 'your-sendgrid-api-key-here']);
        $this->mock(SendGridMailService::class, fn (MockInterface $m) => $m->shouldNotReceive('send'));

        SendNotificationEmail::dispatch('to@example.com', 'S', 'b');

        $this->assertSame(EmailLog::STATUS_SKIPPED, EmailLog::sole()->status);
    }

    public function test_failed_delivery_releases_lock_logs_failure_and_throws(): void
    {
        config(['services.sendgrid.api_key' => 'SG.real']);
        $this->mock(SendGridMailService::class, fn (MockInterface $m) => $m->shouldReceive('send')->andReturnFalse());

        $job = $this->job();
        try {
            $job->handle(app(SendGridMailService::class));
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('to@example.com', $e->getMessage());
        }

        $this->assertSame(EmailLog::STATUS_FAILED, EmailLog::sole()->status);
        $key = 'email_dedup:'.md5('to@example.com|Subject|<p>hi</p>');
        $this->assertFalse(Cache::has($key), 'dedup lock must be released so retry can send');
    }

    public function test_failed_hook_clears_lock(): void
    {
        $key = 'email_dedup:'.md5('to@example.com|Subject|<p>hi</p>');
        Cache::put($key, 'x', 300);

        $this->job()->failed(new \RuntimeException('boom'));

        $this->assertFalse(Cache::has($key));
    }
}
