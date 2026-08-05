<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    private const STYLES = ['adventurer', 'fun-emoji'];

    private const BACKGROUNDS = [
        'f5ede3', 'e8d5c4', 'fce4d6', 'dde5b6', 'c5dedd',
        'd4c4fb', 'ffd6e0', 'ffe0b2', 'c8e6c9', 'bbdefb',
        'f0d9ff', 'fff3cd',
    ];

    public function generateForPersona(string $name, ?string $suffix = null): ?string
    {
        $seed = $suffix ? "{$name}-{$suffix}" : $name;
        $style = self::STYLES[crc32($name) % count(self::STYLES)];
        $bg = self::BACKGROUNDS[crc32($seed) % count(self::BACKGROUNDS)];

        $url = "https://api.dicebear.com/9.x/{$style}/png?"
            . http_build_query(['seed' => $seed, 'size' => 128, 'backgroundColor' => $bg]);

        $response = Http::timeout(15)->get($url);

        if (!$response->successful() || !str_starts_with($response->header('Content-Type', ''), 'image/')) {
            return null;
        }

        $filename = 'personas/avatar-' . substr(md5($seed), 0, 12) . '.png';
        Storage::disk('public')->put($filename, $response->body());

        return rtrim(config('app.url'), '/') . '/storage/' . $filename;
    }

    public function deleteOldAvatar(?string $profileImage): void
    {
        if (!$profileImage) return;

        $parsed = parse_url($profileImage, PHP_URL_PATH);
        $path = $parsed ? ltrim(str_replace('/storage/', '', $parsed), '/') : '';
        if (str_starts_with($path, 'personas/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
