<?php

namespace App\Services;

use App\Models\Curation;
use App\Models\SharedCollection;
use Illuminate\Support\Facades\Storage;

class OgImageResolver
{
    public static function forCuration(Curation $curation): string
    {
        $firstPlace = $curation->places->first();
        if ($firstPlace) {
            $url = self::fromCurationPlace($firstPlace);
            if ($url) return $url;
        }

        foreach ($curation->places->skip(1) as $place) {
            $url = self::fromCurationPlace($place);
            if ($url) return $url;
        }

        if ($curation->cover_image && Storage::disk('public')->exists($curation->cover_image)) {
            return asset('storage/' . $curation->cover_image);
        }

        $mapOg = self::mapOgForCuration($curation);
        if ($mapOg) return asset('storage/' . $mapOg);

        return asset('images/og-image.png');
    }

    public static function forSharedCollection(SharedCollection $collection): string
    {
        foreach ($collection->places as $place) {
            if (!$place->thumbnail_url) continue;
            $storagePath = self::extractStoragePath($place->thumbnail_url);
            if (!$storagePath) continue;

            $ogPath = ImageProcessor::ogPathFor($storagePath);
            if (Storage::disk('public')->exists($ogPath)) {
                return asset('storage/' . $ogPath);
            }
            if (Storage::disk('public')->exists($storagePath)) {
                return asset('storage/' . $storagePath);
            }
        }

        $mapOg = self::mapOgForShared($collection);
        if ($mapOg) return asset('storage/' . $mapOg);

        return asset('images/og-image.png');
    }

    public static function mapOgForShared(SharedCollection $collection): ?string
    {
        $coords = self::extractSharedCoords($collection);
        if (empty($coords)) return null;

        $dir = "shared/{$collection->id}";
        $hash = substr(md5(json_encode($coords)), 0, 10);
        $expected = "{$dir}/og_map_{$hash}.jpg";

        if (Storage::disk('public')->exists($expected)) {
            return $expected;
        }

        return app(ImageProcessor::class)->generateMapOgImage($coords, $dir, $hash);
    }

    public static function mapOgForCuration(Curation $curation): ?string
    {
        $coords = self::extractCurationCoords($curation);
        if (empty($coords)) return null;

        $dir = "curations/{$curation->id}";
        $hash = substr(md5(json_encode($coords)), 0, 10);
        $expected = "{$dir}/og_map_{$hash}.jpg";

        if (Storage::disk('public')->exists($expected)) {
            return $expected;
        }

        return app(ImageProcessor::class)->generateMapOgImage($coords, $dir, $hash);
    }

    private static function extractSharedCoords(SharedCollection $collection): array
    {
        return $collection->places
            ->filter(fn($p) => $p->latitude && $p->longitude)
            ->map(fn($p) => [
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'is_overseas' => (bool) $p->is_overseas,
            ])
            ->values()
            ->all();
    }

    private static function extractCurationCoords(Curation $curation): array
    {
        return $curation->places
            ->filter(fn($p) => $p->latitude && $p->longitude)
            ->map(fn($p) => [
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'is_overseas' => (bool) $p->is_overseas,
            ])
            ->values()
            ->all();
    }

    private static function fromCurationPlace($place): ?string
    {
        $photos = $place->photos;
        if (!$photos || empty($photos)) return null;

        $ogPath = ImageProcessor::ogPathFor($photos[0]);
        if (Storage::disk('public')->exists($ogPath)) {
            return asset('storage/' . $ogPath);
        }

        if (Storage::disk('public')->exists($photos[0])) {
            return asset('storage/' . $photos[0]);
        }

        return null;
    }

    private static function extractStoragePath(string $url): ?string
    {
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) return null;
        $path = preg_replace('#^.*/storage/#', '', $parsed);
        return ($path && $path !== $parsed) ? $path : null;
    }
}
