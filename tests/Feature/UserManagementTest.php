<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->signIn(User::factory()->create(['username' => 'admin', 'role' => 'admin']));
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['name' => 'Pengelola Baru', 'username' => 'pengelola.baru', 'role' => 'editor', 'is_active' => 1,
            'password' => 'Temporary123', 'password_confirmation' => 'Temporary123'], $overrides);
    }

    public function test_editors_cannot_manage_accounts_or_promote_themselves(): void
    {
        $editor = $this->signIn();
        $other = User::factory()->create();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $other))->assertForbidden();
        $this->post(route('users.store'), $this->data(['role' => 'admin']))->assertForbidden();
        $this->put(route('users.update', $editor), $this->data(['role' => 'admin']))->assertForbidden();
        $this->put(route('users.password', $other), $this->data())->assertForbidden();
        $this->assertSame('editor', $editor->fresh()->role);
        $this->assertDatabaseCount('users', 2);
        $this->get(route('home'))->assertOk()->assertDontSee('href="'.route('users.index').'"', false);
        $this->get(route('categories.index'))->assertOk();
    }

    public function test_admin_can_create_edit_disable_and_reactivate_accounts(): void
    {
        $admin = $this->admin();
        $this->get(route('users.index'))->assertOk()->assertSee('admin')->assertDontSee($admin->password);
        $this->get(route('users.create'))->assertOk();
        $this->post(route('users.store'), $this->data(['username' => '  PENGELOLA.BARU  ']))
            ->assertRedirect(route('users.index'))->assertSessionHasNoErrors();
        $user = User::where('username', 'pengelola.baru')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Temporary123', $user->password));
        $this->get(route('users.edit', $user))->assertOk()->assertSee('Reset password')->assertDontSee($user->password);
        $this->put(route('users.update', $user), $this->data(['name' => 'Nama Diperbarui', 'is_active' => 0, 'auth_version' => 9999]))->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(2, $user->fresh()->auth_version);
        $this->put(route('users.update', $user), $this->data(['name' => 'Nama Diperbarui', 'role' => 'admin']))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->is_active);
        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertSame(3, $user->fresh()->auth_version);
    }

    public function test_invalid_duplicate_usernames_roles_and_passwords_are_rejected(): void
    {
        $this->admin();
        foreach ([['username' => 'ADMIN'], ['username' => ['array']], ['username' => 'nama dengan spasi'], ['role' => 'superadmin'],
            ['is_active' => 'invalid'], ['password' => 'abc1', 'password_confirmation' => 'abc1'], ['password_confirmation' => 'different']] as $override) {
            $this->post(route('users.store'), $this->data($override))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_cannot_disable_or_demote_their_own_account(): void
    {
        $admin = $this->admin();
        $this->put(route('users.update', $admin), $this->data(['username' => 'admin', 'is_active' => 0, 'role' => 'admin']))->assertSessionHasErrors('role');
        $this->put(route('users.update', $admin), $this->data(['username' => 'admin', 'role' => 'editor']))->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->delete('/pengguna/'.$admin->id)->assertStatus(405);
        $this->put(route('users.password', $admin), $this->data())->assertRedirect(route('account.password'));
    }

    public function test_admin_password_reset_revokes_old_sessions_and_requires_a_new_password(): void
    {
        $this->admin();
        $user = User::factory()->create(['username' => 'riri']);
        $version = $user->auth_version;
        $this->put(route('users.password', $user), ['password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123'])
            ->assertRedirect(route('users.edit', $user))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('ResetPassword123', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->withSession(['auth_version' => $version]);
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), ['username' => 'riri', 'password' => 'ResetPassword123'])->assertRedirect(route('account.password'));
    }

    public function test_initial_account_command_is_idempotent_and_does_not_reset_existing_passwords(): void
    {
        $question = 'Password awal untuk akun baru (minimal 8 karakter, huruf dan angka)';
        $this->artisan('revita:setup-users')->expectsQuestion($question, 'Temporary123')->assertSuccessful();
        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'admin', 'is_active' => 1]);
        foreach (['naufalzahirr', 'riri', 'yayuk', 'azizul', 'purnamawati', 'rika'] as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->assertTrue(Hash::check('Temporary123', $user->password));
            $this->assertSame('editor', $user->role);
            $this->assertTrue($user->must_change_password);
        }
        $riri = User::where('username', 'riri')->firstOrFail();
        $riri->update(['password' => 'ChangedPassword123', 'must_change_password' => false]);
        $this->artisan('revita:setup-users')->expectsQuestion($question, 'OtherPassword123')->assertSuccessful();
        $this->assertDatabaseCount('users', 7);
        $this->assertTrue(Hash::check('ChangedPassword123', $riri->fresh()->password));
        $this->assertFalse($riri->fresh()->must_change_password);
    }
}
