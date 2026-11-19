<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/** Penanda perangkat pasien (tanpa akun), disimpan di cookie terenkripsi selama 1 tahun. */
class DeviceToken
{
    public const COOKIE = 'device_token';

    public static function fromRequest(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && strlen($token) === 40 ? $token : null;
    }

    public static function generate(): string
    {
        return Str::random(40);
    }

    public static function cookie(string $token): Cookie
    {
        return cookie(self::COOKIE, $token, 60 * 24 * 365);
    }
}
