<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryTester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_and_deletes_category(): void
    {
        $admin = User::factory()->admin()->create();

        $res = $this->actingAs($admin)->postJson('/api/categories', ['categoryName' => '  Payments '])
            ->assertOk()->assertJson(['result' => 'success', 'data' => ['category_name' => 'Payments']]);
        $id = $res->json('data.id');
        $this->assertDatabaseHas('categories', ['id' => $id, 'name' => 'Payments', 'created_by' => $admin->id]);

        $this->actingAs($admin)->deleteJson("/api/categories/{$id}")->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $id]);
        $this->actingAs($admin)->deleteJson("/api/categories/{$id}")->assertNotFound();
    }

    public function test_duplicate_name_is_case_insensitive(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Payments']);

        $this->actingAs($admin)->postJson('/api/categories', ['categoryName' => 'pAyMeNtS'])->assertStatus(409);
        $this->assertSame(1, Category::count());
    }

    public function test_blank_name_is_400(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/categories', ['categoryName' => '   '])->assertStatus(400);
        $this->actingAs($admin)->postJson('/api/categories', [])->assertStatus(400);
    }

    public function test_non_admin_cannot_create_or_delete(): void
    {
        $user = User::factory()->creator()->create();
        $cat = Category::factory()->create();

        $this->actingAs($user)->postJson('/api/categories', ['categoryName' => 'Nope'])->assertForbidden();
        $this->actingAs($user)->deleteJson("/api/categories/{$cat->id}")->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
        $this->assertDatabaseMissing('categories', ['name' => 'Nope']);
    }

    public function test_index_includes_tester_count_and_search(): void
    {
        $user = User::factory()->create();
        $a = Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Beta']);
        CategoryTester::create(['category_id' => $a->id, 'user_id' => User::factory()->create()->id, 'role_id' => 1]);
        CategoryTester::create(['category_id' => $a->id, 'user_id' => User::factory()->create()->id, 'role_id' => 2]);

        $data = $this->actingAs($user)->getJson('/api/categories')->assertOk()->json('data');
        $this->assertSame(['Alpha', 'Beta'], array_column($data, 'name'));
        $this->assertSame(2, $data[0]['testers_count']);
        $this->assertSame(2, $data[0]['tester_count']);
        $this->assertSame('Alpha', $data[0]['category_name']);
        $this->assertSame(0, $data[1]['tester_count']);

        $this->actingAs($user)->getJson('/api/categories?search=bet')->assertJsonCount(1, 'data');
        $simple = $this->actingAs($user)->getJson('/api/categories/simple')->assertOk()->json('data');
        $this->assertSame(['id' => $a->id, 'category_name' => 'Alpha'], $simple[0]);
    }

    public function test_admin_assigns_and_removes_testers(): void
    {
        $admin = User::factory()->admin()->create();
        $cat = Category::factory()->create();
        $u = User::factory()->create();

        $res = $this->actingAs($admin)->postJson('/api/category-testers', ['categoryId' => $cat->id, 'targetUserId' => $u->id, 'roleId' => 3])
            ->assertOk()->assertJson(['result' => 'success']);
        $this->assertDatabaseHas('category_testers', ['category_id' => $cat->id, 'user_id' => $u->id, 'role_id' => 3]);

        $this->actingAs($admin)->postJson('/api/category-testers', ['categoryId' => $cat->id, 'targetUserId' => $u->id, 'roleId' => 3])->assertStatus(400);

        $list = $this->actingAs($admin)->getJson("/api/category-testers/{$cat->id}")->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertSame('Sandbox Tester', $list[0]['roleName']);
        $this->assertSame($u->email, $list[0]['email']);

        $id = $res->json('data.id');
        $this->actingAs($admin)->deleteJson("/api/category-testers/{$id}")->assertOk();
        $this->assertDatabaseCount('category_testers', 0);
        $this->actingAs($admin)->deleteJson("/api/category-testers/{$id}")->assertNotFound();
    }

    public function test_category_tester_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $cat = Category::factory()->create();
        $u = User::factory()->create();

        $this->actingAs($admin)->postJson('/api/category-testers', [])->assertStatus(400);
        $this->actingAs($admin)->postJson('/api/category-testers', ['categoryId' => $cat->id, 'targetUserId' => $u->id, 'roleId' => 7])->assertStatus(400);
        $this->actingAs($admin)->postJson('/api/category-testers', ['categoryId' => 9999, 'targetUserId' => $u->id, 'roleId' => 1])->assertNotFound();
        $this->actingAs($admin)->postJson('/api/category-testers', ['categoryId' => $cat->id, 'targetUserId' => 9999, 'roleId' => 1])->assertNotFound();
    }

    public function test_non_admin_cannot_assign_or_remove_testers(): void
    {
        $user = User::factory()->creator()->create();
        $cat = Category::factory()->create();
        $b = CategoryTester::create(['category_id' => $cat->id, 'user_id' => $user->id, 'role_id' => 1]);

        $this->actingAs($user)->postJson('/api/category-testers', ['categoryId' => $cat->id, 'targetUserId' => $user->id, 'roleId' => 2])->assertForbidden();
        $this->actingAs($user)->deleteJson("/api/category-testers/{$b->id}")->assertForbidden();
        $this->assertDatabaseCount('category_testers', 1);
    }

    public function test_category_testers_index_authorization(): void
    {
        $cat = Category::factory()->create();

        $this->actingAs(User::factory()->create())->getJson("/api/category-testers/{$cat->id}")->assertForbidden();
        $this->actingAs(User::factory()->creator()->create())->getJson("/api/category-testers/{$cat->id}")->assertOk();
        $this->actingAs(User::factory()->admin()->create())->getJson("/api/category-testers/{$cat->id}")->assertOk();
    }
}
