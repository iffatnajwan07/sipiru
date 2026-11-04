<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Penyimpanan key-value; nilai sensitif dienkripsi dengan APP_KEY. */
class SettingRepository
{
    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all()) && $this->all()[$key] !== null && $this->all()[$key] !== '';
    }

    public function set(string $key, ?string $value, bool $encrypted = false): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value' => $value === null || $value === '' ? null : ($encrypted ? Crypt::encryptString($value) : $value),
            'is_encrypted' => $encrypted,
        ]);

        $this->cache = null;
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        $this->cache = null;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return $this->cache = [];
            }

            return $this->cache = Setting::query()->get()->mapWithKeys(function (Setting $setting) {
                $value = $setting->getRawOriginal('value');
                if ($setting->is_encrypted && $value !== null) {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (DecryptException) {
                        $value = null; // APP_KEY berubah → nilai tidak dapat dibaca
                    }
                }

                return [$setting->key => $value];
            })->all();
        } catch (Throwable) {
            // Database belum siap (mis. saat instalasi awal)
            return [];
        }
    }

    public function flush(): void
    {
        $this->cache = null;
    }
}
