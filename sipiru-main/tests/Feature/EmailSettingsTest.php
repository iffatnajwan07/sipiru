<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Notifications\TestMailNotification;
use App\Services\MailSettings;
use App\Services\SettingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'source' => 'database', 'host' => 'smtp.usk.ac.id', 'port' => 587, 'username' => 'noreply',
            'password' => 'S3cret-SMTP!', 'encryption' => 'tls', 'from_address' => 'noreply@usk.ac.id',
            'from_name' => 'Peminjaman Ruang USK', 'admin_notification_address' => 'sarpras@usk.ac.id',
        ], $overrides);
    }

    public function test_smtp_settings_are_saved_with_encrypted_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.email.update'), $this->payload())->assertSessionHasNoErrors();

        $raw = Setting::where('key', 'mail.password')->first();
        $this->assertTrue($raw->is_encrypted);
        $this->assertNotSame('S3cret-SMTP!', $raw->getRawOriginal('value'));
        $this->assertSame('S3cret-SMTP!', Crypt::decryptString($raw->getRawOriginal('value')));

        // Password tidak pernah ditampilkan kembali, dan tidak masuk activity log
        $this->actingAs($admin)->get(route('admin.settings.email'))->assertOk()->assertDontSee('S3cret-SMTP!')->assertSee('tersimpan');
        $this->assertDatabaseHas('activity_logs', ['action' => 'UPDATE_EMAIL_SETTINGS']);
        $this->assertStringNotContainsString('S3cret', ActivityLog::pluck('properties')->toJson());
    }

    public function test_blank_password_keeps_existing_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.settings.email.update'), $this->payload());
        $this->actingAs($admin)->put(route('admin.settings.email.update'), $this->payload(['password' => '', 'host' => 'mail.usk.ac.id']));

        $this->assertSame('S3cret-SMTP!', app(SettingRepository::class)->get('mail.password'));
    }

    public function test_database_smtp_configuration_is_applied_to_mailer(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.email.update'), $this->payload());

        app(SettingRepository::class)->flush();
        app(MailSettings::class)->apply();

        $this->assertSame('smtp.usk.ac.id', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('S3cret-SMTP!', config('mail.mailers.smtp.password'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));
        $this->assertSame('noreply@usk.ac.id', config('mail.from.address'));
        $this->assertSame(['sarpras@usk.ac.id'], app(MailSettings::class)->adminRecipients());
    }

    public function test_env_mode_does_not_override_configuration(): void
    {
        config(['mail.mailers.smtp.host' => 'from-env']);
        $this->actingAs($this->admin())->put(route('admin.settings.email.update'), ['source' => 'env']);

        app(MailSettings::class)->apply();
        $this->assertSame('from-env', config('mail.mailers.smtp.host'));
    }

    public function test_database_mode_requires_host_and_from_address(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.email.update'), ['source' => 'database'])
            ->assertSessionHasErrors(['host', 'port', 'from_address']);
    }

    public function test_admin_can_send_test_email(): void
    {
        Notification::fake();

        $this->actingAs($this->admin())->post(route('admin.settings.email.test'), ['test_email' => 'tes@usk.ac.id'])
            ->assertSessionHas('success');

        Notification::assertSentTo(new AnonymousNotifiable, TestMailNotification::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'tes@usk.ac.id');
    }

    public function test_test_email_failure_is_reported_without_crashing(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'invalid.host.local', 'mail.mailers.smtp.port' => 2525, 'mail.mailers.smtp.timeout' => 2]);

        $this->actingAs($this->admin())->post(route('admin.settings.email.test'), ['test_email' => 'tes@usk.ac.id'])
            ->assertSessionHas('error');
    }
}
