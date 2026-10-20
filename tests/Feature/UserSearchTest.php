<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_query_returns_empty(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->getJson('/api/users/search?q=a')->assertOk()->assertExactJson(['result' => 'success', 'data' => []]);
        $this->actingAs($me)->getJson('/api/users/search')->assertOk()->assertJson(['data' => []]);
        $this->actingAs($me)->getJson('/api/users/search?q=%20%20a%20')->assertJson(['data' => []]);
    }

    public function test_search_matches_email_substring_and_shape(): void
    {
        $me = User::factory()->create();
        User::factory()->create(['email' => 'jane.doe@corp.test', 'name' => 'Jane', 'permission_level' => 'QA']);
        User::factory()->create(['email' => 'other@corp.test']);

        $data = $this->actingAs($me)->getJson('/api/users/search?q=jane')->assertOk()->json('data');
        $this->assertCount(1, $data);
        $this->assertSame(['email', 'userId', 'permissionLevel', 'name'], array_keys($data[0]));
        $this->assertSame('QA', $data[0]['permissionLevel']);
        $this->assertArrayNotHasKey('password', $data[0]);
    }

    public function test_like_wildcards_are_escaped(): void
    {
        $me = User::factory()->create();
        User::factory()->count(3)->create();

        $this->actingAs($me)->getJson('/api/users/search?q=%25%25')->assertJson(['data' => []]);
        $this->actingAs($me)->getJson('/api/users/search?q=__')->assertJson(['data' => []]);
        $this->actingAs($me)->getJson('/api/users/search?q='.urlencode('%@'))->assertJson(['data' => []]);
    }

    public function test_results_are_limited_to_five(): void
    {
        $me = User::factory()->create();
        User::factory()->count(8)->sequence(fn ($s) => ['email' => "match{$s->index}@x.test"])->create();

        $this->actingAs($me)->getJson('/api/users/search?q=match')->assertJsonCount(5, 'data');
    }

    public function test_me_endpoint(): void
    {
        $me = User::factory()->creator()->create(['permission_level' => 'QA']);

        $this->actingAs($me)->getJson('/api/users/me')->assertOk()->assertJson([
            'result' => 'success',
            'data' => [
                'userId' => $me->id,
                'email' => $me->email,
                'permissionLevel' => 'QA',
                'allowCreation' => 'yes',
                'email_notifications_enabled' => false,
            ],
        ])->assertJsonMissingPath('data.password');
    }

    public function test_update_settings(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->putJson('/api/users/settings', ['email_notifications_enabled' => true])->assertOk();
        $this->assertTrue($me->fresh()->email_notifications_enabled);

        $this->actingAs($me)->putJson('/api/users/settings', ['email_notifications_enabled' => false])->assertOk();
        $this->assertFalse($me->fresh()->email_notifications_enabled);
    }

    public function test_update_settings_validation_and_no_mass_assignment(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->putJson('/api/users/settings', [])->assertStatus(422)->assertJsonValidationErrors('email_notifications_enabled');
        $this->actingAs($me)->putJson('/api/users/settings', ['email_notifications_enabled' => 'banana'])->assertStatus(422);

        $this->actingAs($me)->putJson('/api/users/settings', [
            'email_notifications_enabled' => true, 'permission_level' => 'Team Leader (TL)', 'allow_creation' => true,
        ])->assertOk();
        $me->refresh();
        $this->assertSame('Developer', $me->permission_level);
        $this->assertFalse($me->allow_creation);
    }

    public function test_versions_endpoint_validation(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->getJson('/api/versions?type=prod')->assertStatus(400);
        $this->actingAs($me)->getJson('/api/versions?type=live')->assertOk()->assertJson(['result' => 'success', 'data' => []]);
    }
}
