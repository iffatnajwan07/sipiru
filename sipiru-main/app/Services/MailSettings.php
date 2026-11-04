<?php

namespace App\Services;

use App\Models\User;

/**
 * Konfigurasi SMTP mendukung dua mode:
 *  - env      : memakai MAIL_* di .env (default)
 *  - database : memakai pengaturan dari Admin → Settings → Email (password terenkripsi)
 */
class MailSettings
{
    public const SOURCE_ENV = 'env';

    public const SOURCE_DATABASE = 'database';

    public const KEYS = [
        'mail.source', 'mail.host', 'mail.port', 'mail.username', 'mail.password',
        'mail.encryption', 'mail.from_address', 'mail.from_name', 'mail.admin_notification_address',
    ];

    public function __construct(private SettingRepository $settings) {}

    public function source(): string
    {
        return $this->settings->get('mail.source') === self::SOURCE_DATABASE ? self::SOURCE_DATABASE : self::SOURCE_ENV;
    }

    public function usesDatabase(): bool
    {
        return $this->source() === self::SOURCE_DATABASE;
    }

    /** Terapkan pengaturan database ke config runtime Laravel. */
    public function apply(): void
    {
        if (! $this->usesDatabase()) {
            return;
        }

        $encryption = $this->settings->get('mail.encryption');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->settings->get('mail.host'),
            'mail.mailers.smtp.port' => (int) $this->settings->get('mail.port', '587'),
            'mail.mailers.smtp.username' => $this->settings->get('mail.username'),
            'mail.mailers.smtp.password' => $this->settings->get('mail.password'),
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.require_tls' => $encryption === 'tls',
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address' => $this->settings->get('mail.from_address') ?: config('mail.from.address'),
            'mail.from.name' => $this->settings->get('mail.from_name') ?: config('mail.from.name'),
        ]);
    }

    /** Nilai untuk form (password TIDAK pernah dikembalikan). */
    public function formValues(): array
    {
        $db = $this->usesDatabase();

        return [
            'source' => $this->source(),
            'host' => $db ? $this->settings->get('mail.host') : config('mail.mailers.smtp.host'),
            'port' => $db ? $this->settings->get('mail.port') : config('mail.mailers.smtp.port'),
            'username' => $db ? $this->settings->get('mail.username') : config('mail.mailers.smtp.username'),
            'encryption' => $db ? $this->settings->get('mail.encryption') : config('mail.mailers.smtp.encryption'),
            'from_address' => $db ? $this->settings->get('mail.from_address') : config('mail.from.address'),
            'from_name' => $db ? $this->settings->get('mail.from_name') : config('mail.from.name'),
            'admin_notification_address' => $this->adminNotificationAddress(),
            'has_password' => $db ? $this->settings->has('mail.password') : filled(config('mail.mailers.smtp.password')),
        ];
    }

    public function save(array $data): void
    {
        $this->settings->set('mail.source', $data['source']);
        $this->settings->set('mail.admin_notification_address', $data['admin_notification_address'] ?? null);

        if ($data['source'] === self::SOURCE_DATABASE) {
            foreach (['host', 'port', 'username', 'encryption', 'from_address', 'from_name'] as $field) {
                $this->settings->set("mail.$field", isset($data[$field]) ? (string) $data[$field] : null);
            }
            // Password kosong = pertahankan password lama
            if (! empty($data['clear_password'])) {
                $this->settings->set('mail.password', null, true);
            } elseif (filled($data['password'] ?? null)) {
                $this->settings->set('mail.password', $data['password'], true);
            }
        }

        $this->settings->flush();
    }

    public function adminNotificationAddress(): ?string
    {
        return $this->settings->get('mail.admin_notification_address') ?: config('mail.admin_notification_address');
    }

    /** @return list<string> */
    public function adminRecipients(): array
    {
        if ($address = $this->adminNotificationAddress()) {
            return array_values(array_filter(array_map('trim', explode(',', $address))));
        }

        return User::admins()->active()->pluck('email')->all();
    }
}
