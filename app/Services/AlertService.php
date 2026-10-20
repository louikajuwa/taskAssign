<?php

namespace App\Services;

use App\Jobs\SendNotificationEmail;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Testplan;
use App\Models\User;

class AlertService
{
    public function notifyAssignmentAdded(Testplan $testplan, string $role, int $recipientUserId, User $actor): void
    {
        if ($recipientUserId === $actor->id) {
            return;
        }

        $recipient = User::find($recipientUserId);
        if (! $recipient) {
            return;
        }

        $this->createNotification($recipient, 'assignment_added', $testplan, [
            'title' => "New assignment on: {$testplan->title}",
            'message' => "You were assigned as {$role} by {$actor->email}",
            'testplan_id' => $testplan->id,
            'role' => $role,
            'triggered_by' => $actor->email,
        ]);
    }

    public function notifyProgressCompleted(Testplan $testplan, Assignment $assign, User $actor): void
    {
        $nextRoleId = $assign->role_id + 1;
        $nextAssignees = Assignment::where('testplan_id', $assign->testplan_id)
            ->where('role_id', $nextRoleId)
            ->get();

        $recipientIds = [(int) $testplan->created_by];
        foreach ($nextAssignees as $a) {
            $recipientIds[] = (int) $a->user_id;
        }
        $recipientIds = array_values(array_unique(array_filter($recipientIds)));

        foreach ($recipientIds as $uid) {
            if ($uid === $actor->id) {
                continue;
            }
            $recipient = User::find($uid);
            if (! $recipient) {
                continue;
            }

            $this->createNotification($recipient, 'progress_completed', $testplan, [
                'title' => "Stage completed: {$testplan->title}",
                'message' => "{$actor->email} completed {$assign->role_name} stage — your turn next",
                'testplan_id' => $testplan->id,
                'role' => $assign->role_name,
                'triggered_by' => $actor->email,
            ]);
        }
    }

    public function notifyAllCompleted(Testplan $testplan, Assignment $assign, User $actor): void
    {
        $allAssignees = Assignment::where('testplan_id', $assign->testplan_id)->get();

        $recipientIds = [(int) $testplan->created_by];
        foreach ($allAssignees as $a) {
            $recipientIds[] = (int) $a->user_id;
        }
        $recipientIds = array_values(array_unique(array_filter($recipientIds)));

        foreach ($recipientIds as $uid) {
            if ($uid === $actor->id) {
                continue;
            }
            $recipient = User::find($uid);
            if (! $recipient) {
                continue;
            }

            $this->createNotification($recipient, 'all_completed', $testplan, [
                'title' => "Test plan completed: {$testplan->title}",
                'message' => "{$actor->email} completed all stages",
                'testplan_id' => $testplan->id,
                'triggered_by' => $actor->email,
            ]);
        }
    }

    public function notifyProgressReverted(Testplan $testplan, Assignment $assign, User $actor, string $reason): void
    {
        $revertedIds = [(int) $testplan->created_by];

        $devAssigns = Assignment::where('testplan_id', $assign->testplan_id)
            ->where('role_id', 1)->get();
        foreach ($devAssigns as $a) {
            $revertedIds[] = (int) $a->user_id;
        }

        $betweenAssigns = Assignment::where('testplan_id', $assign->testplan_id)
            ->where('role_id', '>', 1)
            ->where('role_id', '<', $assign->role_id)
            ->get();
        foreach ($betweenAssigns as $a) {
            $revertedIds[] = (int) $a->user_id;
        }

        $revertedIds = array_values(array_unique(array_filter($revertedIds)));

        foreach ($revertedIds as $uid) {
            if ($uid === $actor->id) {
                continue;
            }
            $recipient = User::find($uid);
            if (! $recipient) {
                continue;
            }

            $this->createNotification($recipient, 'progress_reverted', $testplan, [
                'title' => "Progress reverted: {$testplan->title}",
                'message' => "{$actor->email} reverted from {$assign->role_name} — review needed",
                'testplan_id' => $testplan->id,
                'role' => $assign->role_name,
                'triggered_by' => $actor->email,
                'reason' => $reason,
            ]);
        }
    }

    public function notifyCommentAdded(Testplan $testplan, Assignment $assign, string $comment, array $files, User $actor): void
    {
        $threadComments = Comment::where('thread_id', $assign->comment_thread_id)->get();

        $participantIds = [];
        foreach ($threadComments as $tc) {
            $participantIds[] = (int) $tc->target_user_id;
            $participantIds[] = (int) $tc->author_id;
        }
        $participantIds[] = (int) $assign->user_id;
        $participantIds[] = (int) $testplan->created_by;
        $participantIds = array_values(array_unique(array_filter($participantIds)));

        $preview = mb_substr($comment, 0, 200);
        if (mb_strlen($comment) > 200) {
            $preview .= '…';
        }

        foreach ($participantIds as $uid) {
            if ($uid === $actor->id) {
                continue;
            }
            $recipient = User::find($uid);
            if (! $recipient) {
                continue;
            }

            $this->createNotification($recipient, 'comment_added', $testplan, [
                'title' => "New comment on: {$testplan->title}",
                'message' => "{$actor->email} commented: {$preview}",
                'testplan_id' => $testplan->id,
                'role' => $assign->role_name,
                'triggered_by' => $actor->email,
                'attachment_count' => count($files),
            ]);
        }
    }

    private function createNotification(User $recipient, string $type, Testplan $testplan, array $extra): void
    {
        Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $extra['title'],
            'message' => $extra['message'],
            'data' => $extra,
        ]);

        if ($recipient->email_notifications_enabled) {
            SendNotificationEmail::dispatch(
                $recipient->email,
                $extra['title'],
                $this->buildEmailHtml($extra['title'], $extra['message'], $testplan),
                $type,
            );
        }
    }

    private function buildEmailHtml(string $title, string $message, Testplan $testplan): string
    {
        $safeUrl = e((string) config('app.url'));
        $safeTitle = e($title);
        $safeMessage = e($message);
        $safePlan = e((string) $testplan->title);
        $safeVersion = e(trim($testplan->version_type.' '.$testplan->version_number));

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #1a1a2e; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0;">TaskAssign Notification</h2>
            </div>
            <div style="padding: 20px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
                <h3 style="color: #1a1a2e;">{$safeTitle}</h3>
                <p style="color: #4b5563;">{$safeMessage}</p>
                <p style="color: #9ca3af; font-size: 12px;">Test Plan: {$safePlan} | Version: {$safeVersion}</p>
                <a href="{$safeUrl}" style="display: inline-block; padding: 10px 20px; background: #009FE3; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px;">Open TaskAssign</a>
            </div>
        </div>
        HTML;
    }
}
