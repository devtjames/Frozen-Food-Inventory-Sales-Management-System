<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $validPassword = 'Strong-Cashier-Password-42!';

    public function test_administrator_can_view_search_and_paginate_cashiers(): void
    {
        $administrator = User::factory()->administrator()->create();
        $target = User::factory()->cashier()->create([
            'name' => 'Amina Cashier',
            'username' => 'amina_cashier',
            'email' => 'amina@example.test',
            'phone' => '+234 801 234 5678',
        ]);
        User::factory()->cashier()->count(10)->create();

        $this->actingAs($administrator)->get('/admin/staff')
            ->assertOk()
            ->assertSee('Staff management')
            ->assertSee('Next')
            ->assertSee('md:hidden', false)
            ->assertSee('hidden md:block', false)
            ->assertSee('open-confirmation', false);

        $this->actingAs($administrator)->get('/admin/staff?search=amina_cashier')
            ->assertOk()
            ->assertSee($target->name)
            ->assertSee($target->email)
            ->assertDontSee('No cashiers match your search');
    }

    public function test_administrator_can_create_cashier_and_submitted_role_is_ignored(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->post('/admin/staff', [
            'name' => 'New Cashier',
            'username' => 'new_cashier',
            'email' => 'cashier@example.test',
            'phone' => '+234 800 000 0000',
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
            'is_active' => '1',
            'role' => UserRole::Administrator->value,
        ]);

        $cashier = User::query()->where('email', 'cashier@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.staff.show', $cashier, absolute: false));
        $this->assertSame(UserRole::Cashier, $cashier->role);
        $this->assertTrue($cashier->is_active);
        $this->assertTrue(Hash::check($this->validPassword, $cashier->password));
        $this->assertNotSame($this->validPassword, $cashier->getRawOriginal('password'));
    }

    public function test_duplicate_email_and_username_are_rejected_case_insensitively(): void
    {
        $administrator = User::factory()->administrator()->create();
        User::factory()->cashier()->create([
            'username' => 'existing_cashier',
            'email' => 'existing@example.test',
        ]);

        $this->actingAs($administrator)->post('/admin/staff', [
            'name' => 'Duplicate Cashier',
            'username' => 'EXISTING_CASHIER',
            'email' => 'EXISTING@EXAMPLE.TEST',
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
            'is_active' => '1',
        ])->assertSessionHasErrors(['username', 'email']);

        $this->assertSame(2, User::query()->count());
    }

    public function test_administrator_can_edit_cashier_information_without_changing_role(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($administrator)->put("/admin/staff/{$cashier->id}", [
            'name' => 'Updated Cashier',
            'username' => 'updated_cashier',
            'email' => 'updated@example.test',
            'phone' => '+234 811 111 1111',
            'role' => UserRole::Administrator->value,
        ])->assertRedirect(route('admin.staff.show', $cashier, absolute: false));

        $cashier->refresh();
        $this->assertSame('Updated Cashier', $cashier->name);
        $this->assertSame('updated_cashier', $cashier->username);
        $this->assertSame('updated@example.test', $cashier->email);
        $this->assertSame('+234 811 111 1111', $cashier->phone);
        $this->assertSame(UserRole::Cashier, $cashier->role);
    }

    public function test_administrator_can_deactivate_and_reactivate_cashier_without_deleting_record(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($administrator)->from(route('admin.staff.show', $cashier))
            ->patch("/admin/staff/{$cashier->id}/status", ['is_active' => '0'])
            ->assertRedirect(route('admin.staff.show', $cashier));

        $this->assertFalse($cashier->refresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'role' => UserRole::Cashier->value]);

        $this->post('/logout');
        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($administrator)->patch("/admin/staff/{$cashier->id}/status", ['is_active' => '1']);
        $this->assertTrue($cashier->refresh()->is_active);
    }

    public function test_deactivated_logged_in_cashier_is_denied_on_next_request(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        DB::table('sessions')->insert([
            'id' => 'cashier-active-session',
            'user_id' => $cashier->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($administrator)
            ->patch("/admin/staff/{$cashier->id}/status", ['is_active' => '0'])
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'cashier-active-session']);
        $this->actingAs($cashier->refresh())->get('/cashier/dashboard')->assertForbidden();
    }

    public function test_administrator_can_reset_cashier_password(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        DB::table('sessions')->insert([
            'id' => 'cashier-password-session',
            'user_id' => $cashier->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($administrator)->put("/admin/staff/{$cashier->id}/password", [
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
        ])->assertRedirect(route('admin.staff.show', $cashier, absolute: false));

        $this->assertTrue(Hash::check($this->validPassword, $cashier->refresh()->password));
        $this->assertFalse(Hash::check('password', $cashier->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'cashier-password-session']);
    }

    public function test_cashier_cannot_access_or_modify_staff_management(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/admin/staff')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/staff/create')->assertForbidden();
        $this->actingAs($cashier)->get("/admin/staff/{$otherCashier->id}")->assertForbidden();
        $this->actingAs($cashier)->post('/admin/staff', [
            'name' => 'Unauthorized Account',
            'email' => 'unauthorized@example.test',
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
            'is_active' => '1',
        ])->assertForbidden();
        $this->actingAs($cashier)->patch("/admin/staff/{$otherCashier->id}/status", ['is_active' => '0'])->assertForbidden();
        $this->actingAs($cashier)->put("/admin/staff/{$otherCashier->id}/password", [
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
        $this->assertTrue($otherCashier->refresh()->is_active);
    }

    public function test_staff_module_cannot_modify_an_administrator_account(): void
    {
        $administrator = User::factory()->administrator()->create();
        $otherAdministrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->get("/admin/staff/{$otherAdministrator->id}")->assertForbidden();
        $this->actingAs($administrator)->patch("/admin/staff/{$otherAdministrator->id}/status", ['is_active' => '0'])->assertForbidden();
        $this->actingAs($administrator)->put("/admin/staff/{$otherAdministrator->id}", [
            'name' => 'Modified Administrator',
            'email' => 'modified@example.test',
        ])->assertForbidden();

        $this->assertTrue($otherAdministrator->refresh()->is_active);
        $this->assertNotSame('Modified Administrator', $otherAdministrator->name);
    }

    public function test_cashier_can_log_in_with_username(): void
    {
        User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create(['username' => 'counter_one']);

        $this->post('/login', ['email' => 'COUNTER_ONE', 'password' => 'password'])
            ->assertRedirect(route('cashier.dashboard', absolute: false));

        $this->assertAuthenticatedAs($cashier);
    }
}
