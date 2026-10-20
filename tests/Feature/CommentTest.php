<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesTestplans;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use MakesTestplans, RefreshDatabase;

    public function test_assignee_posts_and_participants_list(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $dev, 1);

        $res = $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => '  hello  ']))
            ->assertOk()->assertJson(['result' => 'success']);
        $thread = $res->json('commentId');
        $this->assertNotEmpty($thread);
        $this->assertSame($thread, $a->fresh()->comment_thread_id);
        $this->assertDatabaseHas('comments', ['thread_id' => $thread, 'comment' => 'hello', 'author_id' => $dev->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'comment_added']);

        // Second comment reuses the thread.
        $res2 = $this->actingAs($owner)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'reply']))->assertOk();
        $this->assertSame($thread, $res2->json('commentId'));

        foreach ([$dev, $owner] as $who) {
            $list = $this->actingAs($who)->postJson('/api/comments/list', ['commentId' => $thread])->assertOk();
            $this->assertCount(2, $list->json('data'));
            $this->assertSame('reply', $list->json('data.0.comment'));
            $this->assertSame($owner->email, $list->json('data.0.userEmail'));
            $this->assertSame(2, $list->json('pagination.totalRecords'));
        }

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson('/api/comments/list', ['commentId' => $thread])->assertOk();
    }

    public function test_other_assignee_in_same_plan_can_list_but_not_post_to_foreign_thread(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $tester = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $dev, 1);
        $this->addAssignment($plan, $tester, 2, $owner);

        $thread = $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'x']))->json('commentId');

        $this->actingAs($tester)->postJson('/api/comments/list', ['commentId' => $thread])->assertOk();
        $this->actingAs($tester)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'y']))->assertForbidden();
    }

    public function test_non_participant_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $outsider = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $dev, 1);
        $thread = $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'secret']))->json('commentId');

        $this->actingAs($outsider)->postJson('/api/comments/list', ['commentId' => $thread])
            ->assertForbidden()->assertJson(['result' => 'error']);
        $this->actingAs($outsider)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'hax']))->assertForbidden();
        $this->assertDatabaseMissing('comments', ['comment' => 'hax']);
    }

    public function test_unknown_thread_is_404_and_blank_is_400(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->postJson('/api/comments/list', ['commentId' => 'nope'])->assertNotFound();
        $this->actingAs($u)->postJson('/api/comments/list', [])->assertStatus(400);
    }

    public function test_thread_with_comments_but_no_assignment_link_is_404(): void
    {
        $u = User::factory()->create();
        Comment::factory()->create(['thread_id' => 'orphan']);

        $this->actingAs($u)->postJson('/api/comments/list', ['commentId' => 'orphan'])->assertNotFound();
    }

    public function test_comment_length_limit(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => str_repeat('a', 5001)]))
            ->assertStatus(422)->assertJson(['result' => 'error'])->assertJsonValidationErrors('comment');
        $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => str_repeat('a', 5000)]))->assertOk();
    }

    public function test_file_only_comment_ok_and_empty_rejected(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment_file' => ['/storage/uploads/a.png']]))
            ->assertOk()->assertJson(['files' => ['/storage/uploads/a.png']]);
        $this->assertDatabaseHas('comments', ['comment' => '', 'author_id' => $dev->id]);

        $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => '   ']))->assertStatus(400);
    }

    public function test_external_comment_file_rejected_and_missing_fields(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'x', 'comment_file' => ['http://evil.test/a']]))
            ->assertStatus(422);
        $this->actingAs($dev)->postJson('/api/comments', ['comment' => 'x'])->assertStatus(400);
        $this->actingAs($dev)->postJson('/api/comments', ['assignId' => 9999, 'idAssign' => 1, 'targetUserId' => $dev->id, 'comment' => 'x'])->assertNotFound();
    }

    public function test_list_limit_clamp_and_pagination(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);
        $thread = $this->actingAs($dev)->postJson('/api/comments', $this->assignmentBody($a, ['comment' => 'first']))->json('commentId');
        for ($i = 0; $i < 11; $i++) {
            Comment::factory()->create([
                'testplan_id' => $a->testplan_id, 'assignment_role_id' => 1, 'target_user_id' => $dev->id,
                'author_id' => $owner->id, 'thread_id' => $thread,
            ]);
        }

        $res = $this->actingAs($dev)->postJson('/api/comments/list', ['commentId' => $thread, 'limit' => 100000])->assertOk();
        $this->assertSame(100, $res->json('pagination.limit'));
        $this->assertCount(12, $res->json('data'));

        $res = $this->actingAs($dev)->postJson('/api/comments/list', ['commentId' => $thread, 'page' => 2])->assertOk();
        $this->assertCount(2, $res->json('data'));
    }
}
