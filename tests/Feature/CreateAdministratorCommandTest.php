<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_administrator_with_a_hashed_password(): void
    {
        $this->artisan('app:create-administrator', [
            '--name' => 'Store Administrator',
            '--email' => 'admin@example.test',
        ])
            ->expectsQuestion('Password (12+ characters with mixed case, number, and symbol)', 'Strong-Admin-Password-42!')
            ->expectsQuestion('Confirm password', 'Strong-Admin-Password-42!')
            ->expectsOutput('Administrator account created successfully.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(UserRole::Administrator, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('Strong-Admin-Password-42!', $user->password));
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@example.test']);

        $this->artisan('app:create-administrator', [
            '--name' => 'Another Administrator',
            '--email' => 'admin@example.test',
        ])
            ->expectsQuestion('Password (12+ characters with mixed case, number, and symbol)', 'Strong-Admin-Password-42!')
            ->expectsQuestion('Confirm password', 'Strong-Admin-Password-42!')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('email', 'admin@example.test')->count());
    }

    public function test_command_cannot_create_another_administrator_after_setup(): void
    {
        User::factory()->administrator()->create();

        $this->artisan('app:create-administrator')
            ->expectsOutput('An administrator already exists. Initial administrator setup is closed.')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('role', UserRole::Administrator)->count());
    }
}
