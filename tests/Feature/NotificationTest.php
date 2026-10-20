<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_notifications(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $me->id]);
        Notification::factory()->count(2)->create(['user_id' => $other->id]);

        $res = $this->actingAs($me)->getJson('/api/notifications')->assertOk();
        $this->assertCount(3, $res->json('data'));
        $this->assertSame(3, $res->json('pagination.totalRecords'));
        foreach ($res->json('data') as $n) {
            $this->assertSame($me->id, $n['user_id']);
        }
    }

    public function test_unread_filter_and_count(): void
    {
        $me = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $me->id]);
        Notification::factory()->create(['user_id' => $me->id, 'read_at' => now()]);
        Notification::factory()->count(4)->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($me)->getJson('/api/notifications?filter=unread')->assertJsonCount(2, 'data');
        $this->actingAs($me)->getJson('/api/notifications/unread-count')->assertOk()->assertJson(['count' => 2]);
    }

    public function test_mark_read_own_and_other(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = Notification::factory()->create(['user_id' => $me->id]);
        $theirs = Notification::factory()->create(['user_id' => $other->id]);

        $this->actingAs($me)->putJson("/api/notifications/{$mine->id}/read")->assertOk();
        $this->assertNotNull($mine->fresh()->read_at);

        $this->actingAs($me)->putJson("/api/notifications/{$theirs->id}/read")->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->actingAs($me)->putJson('/api/notifications/999999/read')->assertNotFound();
    }

    public function test_read_all_only_affects_own(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $me->id]);
        Notification::factory()->count(2)->create(['user_id' => $other->id]);

        $this->actingAs($me)->putJson('/api/notifications/read-all')->assertOk()->assertJson(['result' => 'success']);

        $this->assertSame(0, Notification::where('user_id', $me->id)->whereNull('read_at')->count());
        $this->assertSame(2, Notification::where('user_id', $other->id)->whereNull('read_at')->count());
    }

    public function test_limit_clamp_and_paging(): void
    {
        $me = User::factory()->create();
        Notification::factory()->count(105)->create(['user_id' => $me->id]);

        $res = $this->actingAs($me)->getJson('/api/notifications?limit=100000')->assertOk();
        $this->assertCount(100, $res->json('data'));
        $this->assertSame(100, $res->json('pagination.limit'));

        $res = $this->actingAs($me)->getJson('/api/notifications?limit=0&page=-4')->assertOk();
        $this->assertSame(1, $res->json('pagination.limit'));
        $this->assertSame(1, $res->json('pagination.currentPage'));

        $this->actingAs($me)->getJson('/api/notifications?page=2&limit=100')->assertJsonCount(5, 'data');
    }

    public function test_page_requires_auth(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/notifications')->assertOk();
    }

    public function test_api_requires_auth(): void
    {
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
        $this->getJson('/api/notifications')->assertUnauthorized();
    }
}
