<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SettingsService
{
    public function __construct(protected GeneralSettings $settings)
    {
    }

    public function set(string $key, mixed $value): void
    {
        if (Str::endsWith($key, ['_key', '_token'])) {
            $value = Crypt::encrypt($value);
        }

        $data = $this->settings->data;
        $data[$key] = $value;
        $this->settings->data = $data;
        $this->settings->save();
    }

    public function get(string $key): mixed
    {
        $value = $this->settings->data[$key] ?? null;

        if ($value && Str::endsWith($key, ['_key', '_token'])) {
            try {
                return Crypt::decrypt($value);
            } catch (\Exception $e) {
                // Return original value if decryption fails (e.g. wasn't encrypted)
                return $value;
            }
        }

        return $value;
    }
}
