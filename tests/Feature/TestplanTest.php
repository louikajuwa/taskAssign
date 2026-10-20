<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MakesTestplans;
use Tests\TestCase;

class TestplanTest extends TestCase
{
    use MakesTestplans, RefreshDatabase;

    public function test_creator_creates_plan_with_assignments_and_csv_columns(): void
    {
        Queue::fake();
        $creator = User::factory()->creator()->create();
        $dev = User::factory()->create();
        $dev2 = User::factory()->create();
        $alpha = User::factory()->create();

        $res = $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload([
            'asignToDeVId' => [$dev->id, $dev2->id],
            'alphaTesterId' => [$alpha->id],
            'itemDataFile' => ['/storage/uploads/abc.png'],
        ]));

        $res->assertOk()->assertJson(['result' => 'success']);
        $plan = Testplan::findOrFail($res->json('id'));

        $this->assertSame($creator->id, $plan->created_by);
        $this->assertSame('Open', $plan->main_status);
        $this->assertSame('beta', $plan->version_type);
        $this->assertSame("{$dev->id},{$dev2->id}", $plan->assign_to_dev_ids);
        $this->assertSame((string) $alpha->id, $plan->alpha_tester_ids);
        $this->assertNull($plan->generic_tester_ids);
        $this->assertSame(['/storage/uploads/abc.png'], $plan->item_data_file);

        $this->assertDatabaseCount('assignments', 3);
        $this->assertDatabaseHas('assignments', [
            'testplan_id' => $plan->id, 'user_id' => $alpha->id, 'role_id' => 4,
            'role_name' => 'Alpha Tester', 'field_name' => 'alphaTesterId',
            'level' => 0, 'what_status' => 'pending', 'assigned_by' => $creator->id,
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $dev->id, 'type' => 'assignment_added']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $creator->id]);
    }

    public function test_admin_without_flag_can_create(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/testplans', $this->planPayload())->assertOk();
    }

    public function test_user_without_creation_permission_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/testplans', $this->planPayload())
            ->assertForbidden()->assertJson(['result' => 'error', 'message' => 'User is not allowed to create']);

        $this->assertDatabaseCount('testplans', 0);
    }

    public function test_missing_required_fields_return_422_with_messages(): void
    {
        $creator = User::factory()->creator()->create();

        $res = $this->actingAs($creator)->postJson('/api/testplans', []);

        $res->assertStatus(422)->assertJson(['result' => 'error']);
        $this->assertSame('Missing or empty parameter: title', $res->json('message'));
        foreach (['title', 'description', 'categoryType', 'caseInformation', 'versionType'] as $f) {
            $this->assertSame("Missing or empty parameter: {$f}", $res->json("errors.{$f}.0"));
        }
    }

    public function test_invalid_category_is_rejected(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['categoryType' => 99999]))
            ->assertStatus(422)->assertJsonValidationErrors('categoryType');
    }

    public function test_invalid_enums_are_rejected(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['versionType' => 'prod']))
            ->assertStatus(422)->assertJsonValidationErrors('versionType');
        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['typeOfStatus' => 'Feature']))
            ->assertStatus(422)->assertJsonValidationErrors('typeOfStatus');
    }

    public function test_external_file_urls_are_rejected(): void
    {
        $creator = User::factory()->creator()->create();

        foreach (['http://evil.example/x.png', 'javascript:alert(1)', '/storage/uploads/../../.env', '/etc/passwd'] as $bad) {
            $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['itemDataFile' => [$bad]]))
                ->assertStatus(422)->assertJsonValidationErrors('itemDataFile.0');
        }
        $this->assertDatabaseCount('testplans', 0);
    }

    public function test_absolute_app_url_storage_file_is_accepted(): void
    {
        $creator = User::factory()->creator()->create();
        $url = rtrim(config('app.url'), '/').'/storage/uploads/ok.pdf';

        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['itemDataFile' => [$url]]))->assertOk();
    }

    public function test_unknown_or_duplicate_tester_ids_are_rejected(): void
    {
        $creator = User::factory()->creator()->create();
        $u = User::factory()->create();

        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['asignToDeVId' => [999999]]))
            ->assertStatus(400)->assertJson(['result' => 'error']);
        $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['betaTesterId' => [$u->id, $u->id]]))
            ->assertStatus(400)->assertJson(['result' => 'error']);
        $this->assertDatabaseCount('testplans', 0);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_description_is_sanitized_on_store_and_update(): void
    {
        $creator = User::factory()->creator()->create();
        $xss = '<p onclick="x()">Hi</p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">l</a>';

        $id = $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload(['description' => $xss]))
            ->assertOk()->json('id');

        $desc = Testplan::find($id)->description;
        $this->assertStringContainsString('Hi', $desc);
        foreach (['<script', 'alert(1)', 'onerror', 'onclick', 'javascript:'] as $needle) {
            $this->assertStringNotContainsString($needle, $desc);
        }

        $this->actingAs($creator)->putJson("/api/testplans/{$id}", $this->updatePayload(['description' => $xss]))->assertOk();
        $desc = Testplan::find($id)->description;
        $this->assertStringNotContainsString('<script', $desc);
        $this->assertStringNotContainsString('onclick', $desc);
    }

    private function updatePayload(array $o = []): array
    {
        return array_merge([
            'title' => 'Updated',
            'description' => '<p>d</p>',
            'caseInformation' => 'ci',
            'mainstatus' => 'Open',
            'versionType' => 'alpha',
            'categoryType' => Category::factory()->create()->id,
        ], $o);
    }

    public function test_list_scoping_for_regular_user_and_admin(): void
    {
        $me = User::factory()->create();
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $mine = Testplan::factory()->create(['created_by' => $me->id]);
        [$assigned] = $this->planWithAssignment($owner, $me, 3);
        $other = Testplan::factory()->create(['created_by' => $owner->id]);
        // id 1 must not match user id 11 etc. (FIND_IN_SET exactness)
        $this->planWithAssignment($owner, $stranger, 2);

        $ids = collect($this->actingAs($me)->postJson('/api/testplans/list')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$mine->id, $assigned->id], $ids);
        $this->assertNotContains($other->id, $ids);

        $admin = User::factory()->admin()->create();
        $res = $this->actingAs($admin)->postJson('/api/testplans/list')->assertOk();
        $this->assertSame(4, $res->json('pagination.totalRecords'));
    }

    public function test_list_shape_and_filters(): void
    {
        $me = User::factory()->creator()->create();
        $cat = Category::factory()->create();
        $a = Testplan::factory()->create(['created_by' => $me->id, 'title' => 'Alpha login', 'category_id' => $cat->id]);
        Testplan::factory()->create(['created_by' => $me->id, 'title' => 'Beta thing', 'main_status' => 'Completed']);
        $this->addAssignment($a, User::factory()->create(), 1, $me);

        $row = $this->actingAs($me)->postJson('/api/testplans/list', ['searchQuery' => 'login'])
            ->assertOk()->assertJsonCount(1, 'data')->json('data.0');
        $this->assertSame($a->id, $row['id']);
        $this->assertSame('Open', $row['mainstatus']);
        $this->assertSame($cat->id, $row['categoryType']);
        $this->assertCount(1, $row['assignments']);
        $this->assertSame(1, $row['assignments'][0]['idAssign']);

        $this->actingAs($me)->postJson('/api/testplans/list', ['searchKey' => 'Completed'])->assertJsonCount(1, 'data');
        $this->actingAs($me)->postJson('/api/testplans/list', ['categoryFilter' => $cat->id])->assertJsonCount(1, 'data');
        $this->actingAs($me)->postJson('/api/testplans/list', ['searchKey' => 'MyCreated'])->assertJsonCount(2, 'data');
        $this->actingAs($me)->postJson('/api/testplans/list', ['searchKey' => 'AssignToMe'])->assertJsonCount(0, 'data');
    }

    public function test_list_search_wildcards_do_not_break(): void
    {
        $me = User::factory()->create();
        Testplan::factory()->create(['created_by' => $me->id, 'title' => 'plain']);

        $this->actingAs($me)->postJson('/api/testplans/list', ['searchQuery' => "'; DROP TABLE testplans; --"])
            ->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('testplans', 1);
    }

    public function test_list_limit_is_clamped(): void
    {
        $admin = User::factory()->admin()->create();
        Testplan::factory()->count(105)->create(['created_by' => $admin->id]);

        $res = $this->actingAs($admin)->postJson('/api/testplans/list', ['limit' => 100000])->assertOk();
        $this->assertCount(100, $res->json('data'));
        $this->assertSame(100, $res->json('pagination.limit'));
        $this->assertSame(2, $res->json('pagination.totalPages'));

        $res = $this->actingAs($admin)->postJson('/api/testplans/list', ['limit' => -5, 'currentPage' => -3])->assertOk();
        $this->assertSame(1, $res->json('pagination.limit'));
        $this->assertSame(1, $res->json('pagination.currentPage'));
        $this->assertCount(1, $res->json('data'));
    }

    public function test_owner_updates_plan(): void
    {
        $owner = User::factory()->creator()->create();
        $plan = Testplan::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->putJson("/api/testplans/{$plan->id}", $this->updatePayload([
            'mainstatus' => 'Completed', 'existingFiles' => ['/storage/uploads/a.pdf'],
        ]))->assertOk()->assertJson(['result' => 'success']);

        $plan->refresh();
        $this->assertSame('Updated', $plan->title);
        $this->assertSame('Completed', $plan->main_status);
        $this->assertSame('alpha', $plan->version_type);
        $this->assertSame(['/storage/uploads/a.pdf'], $plan->item_data_file);
    }

    public function test_admin_can_update_any_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Testplan::factory()->create();

        $this->actingAs($admin)->putJson("/api/testplans/{$plan->id}", $this->updatePayload())->assertOk();
    }

    public function test_update_by_non_owner_is_403_even_if_assigned(): void
    {
        $other = User::factory()->creator()->create();
        $plan = Testplan::factory()->create();
        $this->addAssignment($plan, $other, 1);

        $this->actingAs($other)->putJson("/api/testplans/{$plan->id}", $this->updatePayload())
            ->assertForbidden()->assertJson(['message' => 'Permission denied']);
        $this->assertNotSame('Updated', $plan->fresh()->title);
    }

    public function test_update_nonexistent_is_404_and_checked_before_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/testplans/99999', [])->assertNotFound();
    }

    public function test_update_validation(): void
    {
        $owner = User::factory()->create();
        $plan = Testplan::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->putJson("/api/testplans/{$plan->id}", $this->updatePayload(['mainstatus' => 'Archived']))
            ->assertStatus(422)->assertJsonValidationErrors('mainstatus');

        $res = $this->actingAs($owner)->putJson("/api/testplans/{$plan->id}", [])->assertStatus(422);
        $this->assertSame("Field 'title' cannot be empty", $res->json('message'));

        $this->actingAs($owner)->putJson("/api/testplans/{$plan->id}", $this->updatePayload(['existingFiles' => ['https://evil.test/a']]))
            ->assertStatus(422);
        $this->assertSame('Open', $plan->fresh()->main_status);
    }

    public function test_owner_delete_cascades_assignments_and_comments(): void
    {
        $owner = User::factory()->creator()->create();
        $dev = User::factory()->create();
        [$plan, $assign] = $this->planWithAssignment($owner, $dev);
        Comment::factory()->create([
            'testplan_id' => $plan->id, 'author_id' => $owner->id, 'target_user_id' => $dev->id, 'thread_id' => 't1',
        ]);

        $this->actingAs($owner)->deleteJson("/api/testplans/{$plan->id}")->assertOk()->assertJson(['result' => 'success']);

        $this->assertDatabaseMissing('testplans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('assignments', ['id' => $assign->id]);
        $this->assertDatabaseMissing('comments', ['testplan_id' => $plan->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $owner->id]);
    }

    public function test_delete_by_other_is_403(): void
    {
        $other = User::factory()->creator()->create();
        $plan = Testplan::factory()->create();

        $this->actingAs($other)->deleteJson("/api/testplans/{$plan->id}")->assertForbidden();
        $this->assertDatabaseHas('testplans', ['id' => $plan->id]);
    }

    public function test_admin_deletes_any_and_nonexistent_is_404(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Testplan::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/testplans/{$plan->id}")->assertOk();
        $this->actingAs($admin)->deleteJson('/api/testplans/999999')->assertNotFound()->assertJson(['result' => 'error']);
    }

    public function test_assignment_rows_exist_for_each_role(): void
    {
        Queue::fake();
        $creator = User::factory()->creator()->create();
        $users = User::factory()->count(6)->create();
        $fields = array_keys(Assignment::ROLE_MAP);
        $payload = [];
        foreach ($fields as $i => $f) {
            $payload[$f] = [$users[$i]->id];
        }

        $id = $this->actingAs($creator)->postJson('/api/testplans', $this->planPayload($payload))->assertOk()->json('id');

        foreach ($users as $i => $u) {
            $this->assertDatabaseHas('assignments', ['testplan_id' => $id, 'user_id' => $u->id, 'role_id' => $i + 1]);
        }
    }
}
