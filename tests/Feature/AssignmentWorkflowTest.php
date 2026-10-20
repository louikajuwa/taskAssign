<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesTestplans;
use Tests\TestCase;

class AssignmentWorkflowTest extends TestCase
{
    use MakesTestplans, RefreshDatabase;

    public function test_assignee_updates_own_level(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, [
            'level' => 40, 'description' => 'halfway', 'itemDataFile' => ['/storage/uploads/x.png'],
        ]))->assertOk()->assertJson(['result' => 'success']);

        $a->refresh();
        $this->assertSame(40, $a->level);
        $this->assertSame('pending', $a->what_status);
        $this->assertSame('halfway', $a->description);
        $this->assertSame(['/storage/uploads/x.png'], $a->file_info);
    }

    public function test_level_100_marks_done_and_notifies(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $tester = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $dev, 1);
        $this->addAssignment($plan, $tester, 2, $owner);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 100, 'whatStatus' => 'done']))
            ->assertOk();

        $a->refresh();
        $this->assertSame('done', $a->what_status);
        $this->assertSame('done', $a->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'progress_completed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $tester->id, 'type' => 'progress_completed']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $dev->id]);
    }

    public function test_whatstatus_done_without_level_100_stays_pending(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 50, 'whatStatus' => 'done']))->assertOk();

        $this->assertSame('pending', $a->fresh()->what_status);
    }

    public function test_missing_level_keeps_stored_value(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);
        $a->update(['level' => 30]);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['description' => 'note']))->assertOk();

        $this->assertSame(30, $a->fresh()->level);
    }

    public function test_level_out_of_range_is_422(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        foreach ([150, -1] as $bad) {
            $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => $bad]))
                ->assertStatus(422)->assertJson(['result' => 'error'])->assertJsonValidationErrors('level');
        }
        $this->assertSame(0, $a->fresh()->level);
    }

    public function test_invalid_whatstatus_golive_and_files_are_422(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['whatStatus' => 'weird']))->assertStatus(422);
        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['goLive' => 'maybe']))->assertStatus(422);
        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['itemDataFile' => ['http://x.test/a']]))->assertStatus(422);
        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($a, ['idAssign' => 9]))->assertStatus(422);
    }

    public function test_missing_assign_id_is_400_and_unknown_is_404(): void
    {
        $dev = User::factory()->create();

        $this->actingAs($dev)->putJson('/api/assignments/update', [])->assertStatus(400);
        $this->actingAs($dev)->putJson('/api/assignments/update', ['assignId' => 9999, 'idAssign' => 1, 'targetUserId' => $dev->id])->assertNotFound();
    }

    public function test_other_user_cannot_update(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $intruder = User::factory()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($intruder)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 10]))
            ->assertForbidden()->assertJson(['message' => 'Access Denied']);
        $this->assertSame(0, $a->fresh()->level);
    }

    public function test_owner_and_admin_may_update_on_behalf(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $admin = User::factory()->admin()->create();
        [, $a] = $this->planWithAssignment($owner, $dev, 1);

        $this->actingAs($owner)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 10]))->assertOk();
        $this->actingAs($admin)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 20]))->assertOk();
        $this->assertSame(20, $a->fresh()->level);
    }

    public function test_go_back_resets_dev_and_intermediate_stages(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $generic = User::factory()->create();
        $sandbox = User::factory()->create();
        $alpha = User::factory()->create();
        [$plan, $devA] = $this->planWithAssignment($owner, $dev, 1);
        $genA = $this->addAssignment($plan, $generic, 2, $owner);
        $sbA = $this->addAssignment($plan, $sandbox, 3, $owner);
        $alA = $this->addAssignment($plan, $alpha, 4, $owner);
        foreach ([$devA, $genA, $sbA] as $x) {
            $x->update(['level' => 100, 'what_status' => 'done', 'status' => 'done']);
        }

        $this->actingAs($alpha)->putJson('/api/assignments/update', $this->assignmentBody($alA, [
            'level' => 20, 'whatStatus' => 'go back', 'description' => 'broken',
        ]))->assertOk();

        $devA->refresh();
        $this->assertSame(0, $devA->level);
        $this->assertSame('pending', $devA->what_status);
        foreach ([$genA->fresh(), $sbA->fresh()] as $mid) {
            $this->assertSame(0, $mid->level);
            $this->assertSame('go back', $mid->what_status);
            $this->assertSame('pending', $mid->status);
        }
        $alA->refresh();
        $this->assertSame('go back', $alA->what_status);
        $this->assertSame(20, $alA->level);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'progress_reverted']);
        $this->assertDatabaseHas('notifications', ['user_id' => $dev->id, 'type' => 'progress_reverted']);
    }

    public function test_dev_done_resets_go_back_stages_to_pending(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $generic = User::factory()->create();
        [$plan, $devA] = $this->planWithAssignment($owner, $dev, 1);
        $genA = $this->addAssignment($plan, $generic, 2, $owner);
        $genA->update(['what_status' => 'go back']);

        $this->actingAs($dev)->putJson('/api/assignments/update', $this->assignmentBody($devA, ['level' => 100, 'whatStatus' => 'done']))->assertOk();

        $this->assertSame('pending', $genA->fresh()->what_status);
    }

    public function test_go_live_creates_creator_live_assignment_and_syncs_csv(): void
    {
        $owner = User::factory()->create();
        $beta = User::factory()->create();
        $generic = User::factory()->create();
        [$plan, $betaA] = $this->planWithAssignment($owner, $beta, 5);
        $genA = $this->addAssignment($plan, $generic, 2, $owner);

        $this->actingAs($beta)->putJson('/api/assignments/update', $this->assignmentBody($betaA, ['level' => 100, 'goLive' => 'yes']))->assertOk();

        $this->assertDatabaseHas('assignments', [
            'testplan_id' => $plan->id, 'user_id' => $owner->id, 'role_id' => 6, 'field_name' => 'liveTesterId',
        ]);
        $this->assertSame((string) $owner->id, $plan->fresh()->live_tester_ids);
        $this->assertSame('yes', $betaA->fresh()->go_live);
        $genA->refresh();
        $this->assertSame(100, $genA->level);
        $this->assertSame('done', $genA->what_status);

        // Second go-live does not duplicate.
        $this->actingAs($beta)->putJson('/api/assignments/update', $this->assignmentBody($betaA, ['level' => 100, 'goLive' => 'yes']))->assertOk();
        $this->assertSame(1, Assignment::where('testplan_id', $plan->id)->where('role_id', 6)->count());
        $this->assertSame((string) $owner->id, $plan->fresh()->live_tester_ids);
    }

    public function test_go_live_appends_to_existing_live_ids_and_normalizes_boolean(): void
    {
        $owner = User::factory()->create();
        $beta = User::factory()->create();
        $live = User::factory()->create();
        [$plan, $betaA] = $this->planWithAssignment($owner, $beta, 5);
        $this->addAssignment($plan, $live, 6, $owner);

        $this->actingAs($beta)->putJson('/api/assignments/update', $this->assignmentBody($betaA, ['goLive' => 'true']))->assertOk();

        $this->assertSame("{$live->id},{$owner->id}", $plan->fresh()->live_tester_ids);
        $this->assertSame('yes', $betaA->fresh()->go_live);

        $this->actingAs($beta)->putJson('/api/assignments/update', $this->assignmentBody($betaA, ['goLive' => 'off']))->assertOk();
        $this->assertSame('no', $betaA->fresh()->go_live);
    }

    public function test_live_tester_completion_completes_plan(): void
    {
        $owner = User::factory()->create();
        $live = User::factory()->create();
        [$plan, $a] = $this->planWithAssignment($owner, $live, 6);

        $this->actingAs($live)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 100]))->assertOk();

        $this->assertSame('Completed', $plan->fresh()->main_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'type' => 'all_completed']);

        // Dropping below 100 re-opens it.
        $this->actingAs($live)->putJson('/api/assignments/update', $this->assignmentBody($a, ['level' => 50]))->assertOk();
        $this->assertSame('Open', $plan->fresh()->main_status);
    }

    public function test_store_adds_assignment_and_csv(): void
    {
        $owner = User::factory()->create();
        $u = User::factory()->create();
        $plan = Testplan::factory()->create(['created_by' => $owner->id]);

        $res = $this->actingAs($owner)->postJson('/api/assignments', ['assignId' => $plan->id, 'idAssign' => 3, 'targetUserId' => $u->id])
            ->assertOk()->assertJson(['result' => 'success']);

        $this->assertDatabaseHas('assignments', ['id' => $res->json('id'), 'role_id' => 3, 'role_name' => 'Sandbox Tester', 'field_name' => 'sandboxTesterId']);
        $this->assertSame((string) $u->id, $plan->fresh()->sandbox_tester_ids);
        $this->assertDatabaseHas('notifications', ['user_id' => $u->id, 'type' => 'assignment_added']);
    }

    public function test_store_duplicate_is_409(): void
    {
        $owner = User::factory()->create();
        $u = User::factory()->create();
        [$plan] = $this->planWithAssignment($owner, $u, 2);

        $this->actingAs($owner)->postJson('/api/assignments', ['assignId' => $plan->id, 'idAssign' => 2, 'targetUserId' => $u->id])
            ->assertStatus(409)->assertJson(['result' => 'error']);
        $this->assertSame(1, Assignment::where('testplan_id', $plan->id)->count());
    }

    public function test_store_validation_and_permissions(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $u = User::factory()->create();
        $plan = Testplan::factory()->create(['created_by' => $owner->id]);
        $body = ['assignId' => $plan->id, 'idAssign' => 2, 'targetUserId' => $u->id];

        $this->actingAs($other)->postJson('/api/assignments', $body)->assertForbidden();
        $this->actingAs($owner)->postJson('/api/assignments', [])->assertStatus(400);
        $this->actingAs($owner)->postJson('/api/assignments', ['idAssign' => 9] + $body)->assertStatus(400);
        $this->actingAs($owner)->postJson('/api/assignments', ['targetUserId' => 99999] + $body)->assertStatus(400);
        $this->actingAs($owner)->postJson('/api/assignments', ['assignId' => 99999] + $body)->assertNotFound();
        $this->assertDatabaseCount('assignments', 0);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson('/api/assignments', $body)->assertOk();
    }

    public function test_destroy_removes_assignment_and_csv_entry(): void
    {
        $owner = User::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        [$plan, $assignA] = $this->planWithAssignment($owner, $a, 2);
        $this->addAssignment($plan->fresh(), $b, 2, $owner);

        $this->actingAs($owner)->deleteJson('/api/assignments', $this->assignmentBody($assignA))->assertOk();

        $this->assertDatabaseMissing('assignments', ['id' => $assignA->id]);
        $this->assertSame((string) $b->id, $plan->fresh()->generic_tester_ids);

        $this->actingAs($owner)->deleteJson('/api/assignments', ['assignId' => $plan->id, 'idAssign' => 2, 'targetUserId' => $b->id])->assertOk();
        $this->assertNull($plan->fresh()->generic_tester_ids);
    }

    public function test_destroy_permissions_and_missing(): void
    {
        $owner = User::factory()->create();
        $a = User::factory()->create();
        [, $assign] = $this->planWithAssignment($owner, $a, 2);

        $this->actingAs($a)->deleteJson('/api/assignments', $this->assignmentBody($assign))->assertForbidden();
        $this->assertDatabaseHas('assignments', ['id' => $assign->id]);

        $this->actingAs($owner)->deleteJson('/api/assignments', [])->assertStatus(400);
        $this->actingAs($owner)->deleteJson('/api/assignments', ['assignId' => $assign->testplan_id, 'idAssign' => 5, 'targetUserId' => $a->id])->assertNotFound();
    }
}
