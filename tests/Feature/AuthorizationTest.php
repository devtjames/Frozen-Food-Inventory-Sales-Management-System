<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/cashier/dashboard')->assertRedirect('/login');
    }

    public function test_administrator_cannot_access_cashier_routes(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get('/cashier/dashboard')
            ->assertForbidden();
    }

    public function test_cashier_cannot_access_administrator_routes(): void
    {
        $this->actingAs(User::factory()->cashier()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_dashboard_dispatches_each_role_to_its_authorized_area(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($administrator)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($cashier)->get('/dashboard')->assertRedirect('/cashier/dashboard');
    }

    public function test_inactive_authenticated_user_is_blocked_from_all_protected_routes(): void
    {
        $user = User::factory()->cashier()->inactive()->create();

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/profile/password')->assertForbidden();
        $this->actingAs($user)->get('/cashier/dashboard')->assertForbidden();
    }

    public function test_responsive_navigation_and_logout_are_rendered_for_authenticated_users(): void
    {
        $user = User::factory()->cashier()->create();

        $this->actingAs($user)->get('/cashier/dashboard')
            ->assertOk()
            ->assertSee('aria-label="Open navigation"', false)
            ->assertSee('Collapse sidebar')
            ->assertSee(route('logout'), false)
            ->assertSee('overflow-x-hidden', false);
    }
}
