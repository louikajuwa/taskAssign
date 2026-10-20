<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
    }

    public function test_valid_login_redirects_and_authenticates(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_show_error(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->from('/login')->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
        }

        $res = $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong']);
        $res->assertStatus(302);
        $res->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', $res->baseResponse->getSession()->get('errors')->first('email'));

        // Even the correct password is blocked while throttled.
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_throttle_is_keyed_per_email(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        User::factory()->create(['email' => 'b@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'b@example.com', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_successful_login_clears_throttle(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect('/');
        $this->post('/logout');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
        }
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_pages(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/notifications')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_guest_gets_json_401_on_api(): void
    {
        $this->getJson('/api/users/me')->assertUnauthorized();
        $this->get('/api/categories', ['Accept' => 'text/html'])->assertUnauthorized();
        $this->postJson('/api/testplans/list')->assertUnauthorized();
        $this->postJson('/api/files/upload')->assertUnauthorized();
    }

    public function test_authenticated_user_sees_home(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk();
    }
}
