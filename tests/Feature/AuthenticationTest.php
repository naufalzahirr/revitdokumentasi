<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function user(array $data = []): User
    {
        return User::factory()->create(array_merge(['username' => 'riri', 'password' => 'Temporary123', 'must_change_password' => true], $data));
    }

    private function note(): Document
    {
        $document = Document::create(['category' => 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT-001']);
        Storage::disk('local')->put('receipt.jpg', 'receipt-image');
        Storage::disk('local')->put('photo.jpg', 'activity-image');
        $document->forceFill(['receipt_image_path' => 'receipt.jpg', 'share_token' => Str::random(48)])->save();
        $document->photos()->create(['path' => 'photo.jpg', 'caption' => 'Atap baru.']);

        return $document;
    }

    public function test_guests_cannot_open_management_pages_images_or_write_data(): void
    {
        $document = $this->note();
        $urls = [route('home'), route('documents.index'), route('documents.create'), route('documents.show', $document),
            route('documents.edit', $document), route('documents.print', $document), route('documents.voucher', $document),
            route('documents.receipt-image', $document), route('photos.show', $document->photos->first()),
            route('categories.index'), route('users.index'), route('account.password')];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post(route('documents.store'), [])->assertRedirect(route('login'));
        $this->put(route('documents.update', $document), [])->assertRedirect(route('login'));
        $this->delete(route('documents.destroy', $document))->assertRedirect(route('login'));
        $this->post(route('documents.share', $document))->assertRedirect(route('login'));
        $this->post(route('categories.store'), ['name' => 'Tidak boleh'])->assertRedirect(route('login'));
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('documents', 1);
        $this->getJson(route('documents.index'))->assertUnauthorized();
    }

    public function test_shared_links_remain_accessible_without_login_and_do_not_expose_management(): void
    {
        $document = $this->note();
        $this->get(route('shared.show', $document->share_token))->assertOk()->assertDontSee('Edit nota')
            ->assertDontSee('Pengguna')->assertDontSee('Keluar')->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('shared.print', $document->share_token))->assertOk()->assertSee('BUKTI PENGELUARAN DANA');
        $this->get(route('shared.receipt-image', $document->share_token))->assertOk();
        $this->get(route('shared.photo', ['document' => $document->share_token, 'photo' => $document->photos->first()]))->assertOk();
    }

    public function test_login_requires_password_change_then_allows_normal_use_and_logout(): void
    {
        $user = $this->user();
        $this->get(route('login'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $oldSession = session()->getId();
        $this->post(route('login.store'), ['username' => '  RIRI  ', 'password' => 'Temporary123'])
            ->assertRedirect(route('account.password'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->get(route('home'))->assertRedirect(route('account.password'));
        $this->post(route('documents.store'), [])->assertRedirect(route('account.password'));
        $this->get(route('users.index'))->assertRedirect(route('account.password'));
        $this->get(route('account.password'))->assertOk()->assertSee('Ganti password awal');
        $this->put(route('account.password.update'), ['current_password' => 'Temporary123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertFalse($user->fresh()->must_change_password);
        $this->get(route('home'))->assertOk()->assertSee('riri')->assertDontSee('>Pengguna<', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_wrong_inactive_and_malformed_logins_are_rejected_and_throttled(): void
    {
        $user = $this->user();
        $this->post(route('login.store'), ['username' => ['riri'], 'password' => 'Temporary123'])->assertSessionHasErrors('username');
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['username' => 'riri', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->post(route('login.store'), ['username' => 'riri', 'password' => 'Temporary123'])->assertSessionHasErrors('username');
        $this->assertGuest();
        RateLimiter::clear('login:'.hash('sha256', 'riri|127.0.0.1'));
        $user->update(['is_active' => false]);
        $this->post(route('login.store'), ['username' => 'riri', 'password' => 'Temporary123'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_password_change_rejects_wrong_current_same_short_and_mismatched_passwords(): void
    {
        $user = $this->signIn($this->user());
        $cases = [
            ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'],
            ['current_password' => 'Temporary123', 'password' => 'Temporary123', 'password_confirmation' => 'Temporary123'],
            ['current_password' => 'Temporary123', 'password' => 'abc1', 'password_confirmation' => 'abc1'],
            ['current_password' => 'Temporary123', 'password' => '12345678', 'password_confirmation' => '12345678'],
            ['current_password' => 'Temporary123', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'],
            ['current_password' => 'Temporary123', 'password' => str_repeat('a', 72).'1', 'password_confirmation' => str_repeat('a', 72).'1'],
            ['current_password' => 'Temporary123', 'password' => str_repeat('é', 40).'a1', 'password_confirmation' => str_repeat('é', 40).'a1'],
            ['current_password' => 'Temporary123', 'password' => "New\0Password123", 'password_confirmation' => "New\0Password123"],
            ['current_password' => 'Temporary123', 'password' => 'NewPassword123', 'password_confirmation' => 'Different123'],
        ];
        foreach ($cases as $case) {
            $this->put(route('account.password.update'), $case)->assertSessionHasErrors();
        }
        $this->assertTrue(Hash::check('Temporary123', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_deactivated_or_revoked_sessions_are_logged_out(): void
    {
        $user = $this->user(['must_change_password' => false]);
        $this->signIn($user);
        $user->update(['is_active' => false]);
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
        $user->update(['is_active' => true]);
        $this->signIn($user);
        $user->forceFill(['auth_version' => $user->auth_version + 1])->save();
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
