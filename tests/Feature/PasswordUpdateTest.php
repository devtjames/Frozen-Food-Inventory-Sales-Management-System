<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'New-Secure-Password-42!',
            'password_confirmation' => 'New-Secure-Password-42!',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('New-Secure-Password-42!', $user->refresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile/password')->put('/profile/password', [
            'current_password' => 'incorrect-password',
            'password' => 'New-Secure-Password-42!',
            'password_confirmation' => 'New-Secure-Password-42!',
        ])->assertRedirect('/profile/password')->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'New-Secure-Password-42!',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');
    }
}
