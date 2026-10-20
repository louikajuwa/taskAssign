<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MakesTestplans;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use MakesTestplans, RefreshDatabase;

    private function userPayload(array $o = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'permission_level' => 'QA',
            'allow_creation' => true,
        ], $o);
    }

    public function test_non_admin_is_forbidden_from_admin_pages(): void
    {
        $user = User::factory()->creator()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/categories')->assertForbidden();
        $this->actingAs($user)->postJson('/admin/users', $this->userPayload())->assertForbidden()
            ->assertJson(['result' => 'error']);
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_non_admin_cannot_update_or_delete_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->putJson("/admin/users/{$other->id}", $this->userPayload())->assertForbidden();
        $this->actingAs($user)->deleteJson("/admin/users/{$other->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_admin_can_view_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/categories')->assertOk();
    }

    public function test_pm_integrator_is_admin(): void
    {
        $pm = User::factory()->create(['permission_level' => 'Project Manager (PM) & Integrator']);

        $this->actingAs($pm)->get('/admin/users')->assertOk();
    }

    public function test_admin_creates_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload())
            ->assertOk()->assertJson(['result' => 'success']);

        $u = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($u);
        $this->assertTrue($u->allow_creation);
        $this->assertSame('QA', $u->permission_level);
        $this->assertNotSame('secret123', $u->password);
    }

    public function test_create_user_validation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/admin/users', [])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password', 'permission_level']);

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload(['password_confirmation' => 'nope']))
            ->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload(['permission_level' => 'Emperor']))
            ->assertStatus(422)->assertJsonValidationErrors(['permission_level']);

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload(['email' => 'not-an-email']))
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'new@example.com']);

        $this->actingAs($admin)->postJson('/admin/users', $this->userPayload())
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_admin_updates_user_keeping_password_when_blank(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create();
        $oldHash = $u->password;

        $this->actingAs($admin)->putJson("/admin/users/{$u->id}", [
            'name' => 'Renamed', 'email' => $u->email, 'permission_level' => 'Designer', 'allow_creation' => false,
        ])->assertOk();

        $u->refresh();
        $this->assertSame('Renamed', $u->name);
        $this->assertSame('Designer', $u->permission_level);
        $this->assertSame($oldHash, $u->password);
    }

    public function test_update_user_unique_email_ignores_self_but_not_others(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create();
        $other = User::factory()->create();

        $base = ['name' => 'X', 'permission_level' => 'QA'];
        $this->actingAs($admin)->putJson("/admin/users/{$u->id}", $base + ['email' => $u->email])->assertOk();
        $this->actingAs($admin)->putJson("/admin/users/{$u->id}", $base + ['email' => $other->email])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_admin_can_change_password(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create();

        $this->actingAs($admin)->putJson("/admin/users/{$u->id}", [
            'name' => 'X', 'email' => $u->email, 'permission_level' => 'QA',
            'password' => 'brandnewpass', 'password_confirmation' => 'brandnewpass',
        ])->assertOk();

        $this->assertTrue(Hash::check('brandnewpass', $u->fresh()->password));
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->deleteJson("/admin/users/{$admin->id}")
            ->assertForbidden()->assertJson(['result' => 'error']);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_user_with_work(): void
    {
        $admin = User::factory()->admin()->create();

        $owner = User::factory()->create();
        Testplan::factory()->create(['created_by' => $owner->id]);
        $this->actingAs($admin)->deleteJson("/admin/users/{$owner->id}")->assertStatus(409);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);

        $assignee = User::factory()->create();
        $this->planWithAssignment(User::factory()->create(), $assignee);
        $this->actingAs($admin)->deleteJson("/admin/users/{$assignee->id}")->assertStatus(409);

        $catOwner = User::factory()->create();
        Category::factory()->create(['created_by' => $catOwner->id]);
        $this->actingAs($admin)->deleteJson("/admin/users/{$catOwner->id}")->assertStatus(409);
    }

    public function test_admin_deletes_clean_user_and_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create();

        $this->actingAs($admin)->deleteJson("/admin/users/{$u->id}")
            ->assertOk()->assertJson(['result' => 'success']);

        $this->assertDatabaseMissing('users', ['id' => $u->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id]);
    }

    public function test_delete_unknown_user_is_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->deleteJson('/admin/users/999999')->assertNotFound();
    }
}
