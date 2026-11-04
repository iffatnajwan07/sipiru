<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Notifications\TestMailNotification;
use App\Services\ActivityLogger;
use App\Services\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailSettingController extends Controller
{
    public function __construct(private MailSettings $mail) {}

    public function edit(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.settings.email', ['values' => $this->mail->formValues()]);
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $db = $request->input('source') === MailSettings::SOURCE_DATABASE;
        $data = $request->validate([
            'source' => ['required', Rule::in([MailSettings::SOURCE_ENV, MailSettings::SOURCE_DATABASE])],
            'host' => [Rule::requiredIf($db), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($db), 'nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'clear_password' => ['boolean'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'from_address' => [Rule::requiredIf($db), 'nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:150'],
            'admin_notification_address' => ['nullable', 'string', 'max:500', function ($attr, $value, $fail) {
                foreach (array_filter(array_map('trim', explode(',', (string) $value))) as $email) {
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail("Email admin tidak valid: {$email}");
                    }
                }
            }],
        ], [], [
            'host' => 'SMTP host', 'port' => 'SMTP port', 'from_address' => 'email pengirim',
            'admin_notification_address' => 'email notifikasi admin',
        ]);

        $this->mail->save($data);
        // Worker queue memuat ulang konfigurasi mail
        rescue(fn () => Artisan::call('queue:restart'), report: false);

        $logger->log('UPDATE_EMAIL_SETTINGS', null, 'Mengubah pengaturan email (mode: '.$data['source'].')', [
            'source' => $data['source'],
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'password_changed' => filled($data['password'] ?? null) || ! empty($data['clear_password']),
        ]);

        return back()->with('success', 'Pengaturan email disimpan.');
    }

    public function test(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('manage', Setting::class);
        $data = $request->validate(['test_email' => ['required', 'email']], [], ['test_email' => 'email tujuan']);

        // Gunakan konfigurasi terbaru (hindari mailer yang sudah di-cache)
        $this->mail->apply();
        Mail::purge('smtp');

        try {
            Notification::route('mail', $data['test_email'])->notifyNow(new TestMailNotification);
        } catch (Throwable $e) {
            $logger->log('TEST_SMTP_FAILED', null, 'Test SMTP gagal ke '.$data['test_email']);
            // Pesan error dipangkas & tidak memuat password
            $message = Str::limit(str_replace((string) config('mail.mailers.smtp.password'), '***', $e->getMessage()), 300);

            return back()->withInput()->with('error', 'Gagal mengirim email test: '.$message);
        }

        $logger->log('TEST_SMTP', null, 'Mengirim email test ke '.$data['test_email']);

        return back()->with('success', 'Email test berhasil dikirim ke '.$data['test_email'].'.');
    }
}
