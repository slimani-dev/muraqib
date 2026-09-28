<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A URL that can be reached from the internet: no localhost, private or
 * reserved IPs, and no LAN-only host names. The dashboard may be opened
 * from outside the home network, so status checks must use such URLs.
 */
class PublicUrl implements ValidationRule
{
    /** @var list<string> */
    private const LOCAL_SUFFIXES = ['.local', '.lan', '.home', '.internal', '.localdomain', '.home.arpa'];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isPublic($value)) {
            $fail('The :attribute must be a public URL (no localhost, private IPs or LAN-only host names).');
        }
    }

    public static function isPublic(?string $url): bool
    {
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;

        if (! is_string($host) || $host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($host, '[]'));

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_contains($host, '.')
            && $host !== 'localhost'
            && ! Str::endsWith($host, ['.localhost', ...self::LOCAL_SUFFIXES]);
    }
}
