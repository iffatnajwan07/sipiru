<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogger
{
    /** Atribut yang tidak boleh pernah masuk ke log. */
    private const REDACTED_KEYS = ['password', 'smtp_password', 'mail_password', 'remember_token', 'ktp_path', 'verification_token', 'public_token'];

    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $actor = null,
    ): ActivityLog {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return ActivityLog::create([
            'user_id' => ($actor ?? auth()->user())?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? $this->labelFor($subject) : null,
            'description' => $description ? Str::limit($description, 250) : null,
            'properties' => $properties ? $this->redact($properties) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }

    /** Log perubahan: hanya field yang berubah dicatat sebagai before/after. */
    public function logChanges(string $action, Model $subject, array $before, array $after, ?string $description = null): ?ActivityLog
    {
        $changedBefore = [];
        $changedAfter = [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) != $value) {
                $changedBefore[$key] = $before[$key] ?? null;
                $changedAfter[$key] = $value;
            }
        }

        if ($changedAfter === []) {
            return null;
        }

        return $this->log($action, $subject, $description, ['before' => $changedBefore, 'after' => $changedAfter]);
    }

    private function labelFor(Model $subject): string
    {
        foreach (['booking_number', 'code', 'year', 'email', 'key', 'name'] as $attribute) {
            if (filled($subject->getAttribute($attribute))) {
                return (string) $subject->getAttribute($attribute);
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    private function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (is_array($value)) {
                $properties[$key] = $this->redact($value);
            } elseif (in_array($key, self::REDACTED_KEYS, true)) {
                $properties[$key] = '[redacted]';
            }
        }

        return $properties;
    }
}
