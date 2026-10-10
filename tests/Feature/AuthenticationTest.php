<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_requires_owner_setup_and_registration_is_not_exposed(): void
    {
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertSee('Create the owner account');
        $this->get('/register')->assertNotFound();
    }

    public function test_owner_can_create_the_one_time_administrator_and_is_signed_in(): void
    {
        $this->post('/setup', [
            'name' => 'Store Owner',
            'username' => 'store_owner',
            'email' => 'OWNER@EXAMPLE.TEST',
            'phone' => '+234 800 000 0000',
            'password' => 'Owner-Secure-Password-42!',
            'password_confirmation' => 'Owner-Secure-Password-42!',
        ])->assertRedirect(route('admin.dashboard'));

        $administrator = User::query()->sole();
        $this->assertSame(UserRole::Administrator, $administrator->role);
        $this->assertSame('store_owner', $administrator->username);
        $this->assertSame('owner@example.test', $administrator->email);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check('Owner-Secure-Password-42!', $administrator->password));
        $this->assertAuthenticatedAs($administrator);
    }

    public function test_owner_setup_rejects_weak_credentials_and_closes_after_first_administrator(): void
    {
        $this->post('/setup', [
            'name' => 'Store Owner',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['email', 'password']);

        User::factory()->administrator()->create();

        $this->get('/setup')->assertRedirect('/login');
        $this->post('/setup', [
            'name' => 'Second Owner',
            'email' => 'second@example.test',
            'password' => 'Second-Secure-Password-42!',
            'password_confirmation' => 'Second-Secure-Password-42!',
        ])->assertForbidden();
        $this->assertSame(1, User::query()->where('role', UserRole::Administrator)->count());
    }

    public function test_administrator_can_log_in_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->administrator()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_cashier_can_log_in_and_is_redirected_to_cashier_dashboard(): void
    {
        User::factory()->administrator()->create();
        $user = User::factory()->cashier()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('cashier.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->administrator()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'not-the-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->administrator()->create();
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->administrator()->create();
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email', __('auth.throttle', ['seconds' => 60, 'minutes' => 1]));
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_passwords_are_hashed(): void
    {
        $user = User::factory()->create(['password' => 'a-plain-test-password']);

        $this->assertNotSame('a-plain-test-password', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('a-plain-test-password', $user->password));
    }
}
