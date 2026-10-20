<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationEmail;
use App\Models\Testplan;
use App\Models\User;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AlertServiceEmailEscapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_body_escapes_user_controlled_values(): void
    {
        Queue::fake();
        $actor = User::factory()->create(['email' => 'actor@example.com']);
        $recipient = User::factory()->create(['email_notifications_enabled' => true]);
        $plan = Testplan::factory()->create([
            'title' => '<script>alert("t")</script>',
            'version_type' => '<img src=x onerror=1>',
            'version_number' => '"><b>',
        ]);

        app(AlertService::class)->notifyAssignmentAdded($plan, 'Developer', $recipient->id, $actor);

        Queue::assertPushed(SendNotificationEmail::class, function ($job) use ($recipient) {
            $this->assertSame($recipient->email, $job->to);
            $this->assertStringNotContainsString('<script>', $job->htmlBody);
            $this->assertStringNotContainsString('<img src=x', $job->htmlBody);
            $this->assertStringNotContainsString('<b>', $job->htmlBody);
            $this->assertStringContainsString('&lt;script&gt;', $job->htmlBody);
            $this->assertStringContainsString('&lt;img src=x onerror=1&gt;', $job->htmlBody);

            // The subject is plain text for the mail header; HTML body is the only HTML surface.
            return true;
        });
    }

    public function test_notification_row_created_and_email_skipped_when_disabled(): void
    {
        Queue::fake();
        $actor = User::factory()->create();
        $recipient = User::factory()->create(['email_notifications_enabled' => false]);
        $plan = Testplan::factory()->create();

        app(AlertService::class)->notifyAssignmentAdded($plan, 'QA', $recipient->id, $actor);

        $this->assertDatabaseHas('notifications', ['user_id' => $recipient->id, 'type' => 'assignment_added']);
        Queue::assertNothingPushed();
    }

    public function test_no_self_notification_and_unknown_recipient_ignored(): void
    {
        Queue::fake();
        $actor = User::factory()->create(['email_notifications_enabled' => true]);
        $plan = Testplan::factory()->create();
        $svc = app(AlertService::class);

        $svc->notifyAssignmentAdded($plan, 'Developer', $actor->id, $actor);
        $svc->notifyAssignmentAdded($plan, 'Developer', 999999, $actor);

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
    }
}
