<?php

namespace Tests\Feature\Auth;

use App\Auth\Contracts\AuthenticationProvider;
use App\Auth\Providers\SimpegAuthProvider;
use App\Enums\UserType;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_email_or_nip(): void
    {
        $admin = $this->admin(['email' => 'admin@usk.ac.id', 'nip' => '123456']);

        $this->post('/admin/login', ['login' => 'admin@usk.ac.id', 'password' => 'Password123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/admin/login', ['login' => '123456', 'password' => 'Password123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_cannot_login_via_admin_login(): void
    {
        $this->pegawai(['email' => 'p@usk.ac.id']);

        $this->post('/admin/login', ['login' => 'p@usk.ac.id', 'password' => 'Password123'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_pegawai_login_via_local_development_provider(): void
    {
        $pegawai = $this->pegawai(['nip' => '199001012020121001']);

        $this->post('/login/pegawai', ['nip' => '199001012020121001', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($pegawai);

        $log = LoginLog::first();
        $this->assertSame('simpeg:local', $log->method);
        $this->assertNotNull($pegawai->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGIN', 'user_id' => $pegawai->id]);
    }

    public function test_pegawai_login_with_wrong_password_fails(): void
    {
        $this->pegawai(['nip' => '111']);

        $this->post('/login/pegawai', ['nip' => '111', 'password' => 'salah'])->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    public function test_simpeg_provider_not_configured_shows_friendly_error(): void
    {
        config(['auth_providers.internal' => 'simpeg', 'auth_providers.simpeg.base_url' => null]);
        $this->assertInstanceOf(SimpegAuthProvider::class, app(AuthenticationProvider::class));

        $this->post('/login/pegawai', ['nip' => '111', 'password' => 'x'])
            ->assertSessionHasErrors(['nip' => 'Integrasi SIMPEG belum dikonfigurasi. Hubungi administrator sistem.']);
        $this->assertGuest();
    }

    public function test_external_user_can_register_with_private_ktp(): void
    {
        Storage::fake('local');

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'Budi@Example.com',
            'phone' => '0821-1234-5678',
            'nik' => '1171010101900001',
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 300, 'application/pdf'),
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'policy' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserType::Eksternal, $user->user_type);
        $this->assertSame('082112345678', $user->phone);
        $this->assertNotSame('Rahasia123', $user->getRawOriginal('password'));
        $this->assertTrue($user->hasAcceptedCurrentPolicy());
        Storage::disk('local')->assertExists($user->ktp_path);
        $this->assertStringStartsWith('ktp/', $user->ktp_path);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validation_rules(): void
    {
        Storage::fake('local');
        $this->eksternal(['email' => 'ada@example.com', 'nik' => '1111111111111111']);

        $this->post('/register', [
            'name' => 'X Y Z',
            'email' => 'ada@example.com',
            'phone' => '12345',
            'nik' => '1111111111111111',
            'ktp' => UploadedFile::fake()->create('ktp.exe', 10, 'application/x-msdownload'),
            'password' => 'lemah',
            'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors(['email', 'phone', 'nik', 'ktp', 'password', 'policy']);

        $this->post('/register', [
            'name' => 'Tanpa KTP', 'email' => 'baru@example.com', 'phone' => '081234567890', 'nik' => '2222222222222222',
            'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123', 'policy' => '1',
        ])->assertSessionHasErrors('ktp');

        $this->post('/register', [
            'name' => 'KTP Besar', 'email' => 'besar@example.com', 'phone' => '081234567890', 'nik' => '3333333333333333',
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 5000, 'application/pdf'),
            'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123', 'policy' => '1',
        ])->assertSessionHasErrors('ktp');

        $this->assertGuest();
    }

    public function test_external_user_can_login(): void
    {
        $user = $this->eksternal(['email' => 'luar@example.com']);

        $this->post('/login/eksternal', ['email' => 'luar@example.com', 'password' => 'Password123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->eksternal(['email' => 'off@example.com', 'is_active' => false]);

        $this->post('/login/eksternal', ['email' => 'off@example.com', 'password' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_records_logout_time(): void
    {
        $user = $this->eksternal(['email' => 'luar@example.com']);
        $this->post('/login/eksternal', ['email' => 'luar@example.com', 'password' => 'Password123']);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertNotNull(LoginLog::where('user_id', $user->id)->first()->logout_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'LOGOUT', 'user_id' => $user->id]);
    }

    public function test_user_must_accept_policy_before_using_system(): void
    {
        $user = User::factory()->pegawai()->withoutPolicy()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('policy.show'));
        $this->actingAs($user)->post('/kebijakan', [])->assertSessionHasErrors('agree');
        $this->actingAs($user)->post('/kebijakan', ['agree' => '1'])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->policy_accepted_at);
        $this->assertSame(config('booking.policy_version'), $user->policy_version);
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_new_policy_version_requires_reacceptance(): void
    {
        $user = $this->pegawai();
        config(['booking.policy_version' => '2.0']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('policy.show'));
    }

    public function test_login_is_rate_limited(): void
    {
        $this->eksternal(['email' => 'luar@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/eksternal', ['email' => 'luar@example.com', 'password' => 'salah']);
        }
        $this->post('/login/eksternal', ['email' => 'luar@example.com', 'password' => 'salah'])->assertStatus(429);
    }
}
