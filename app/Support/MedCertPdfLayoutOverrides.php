<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class MedCertPdfLayoutOverrides
{
    private const PATH = 'app/med_cert_layout_overrides.json';

    private static ?array $cache = null;

    public static function path(): string
    {
        return storage_path(self::PATH);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (! File::exists(self::path())) {
            self::syncFromEffectiveLayout();
        }

        $raw = File::get(self::path());
        $decoded = json_decode($raw, true);
        self::$cache = is_array($decoded) ? $decoded : [];

        return self::$cache;
    }

    public static function save(array $data): void
    {
        self::$cache = $data;
        File::ensureDirectoryExists(dirname(self::path()));
        File::put(
            self::path(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
        self::flush();
    }

    public static function reset(): void
    {
        if (File::exists(self::path())) {
            File::delete(self::path());
        }
        self::$cache = null;
    }

    public static function hasLayoutData(): bool
    {
        $o = self::all();
        return !empty($o['page1']);
    }

    public static function syncFromEffectiveLayout(): void
    {
        self::$cache = null;
        $config = config('med_cert_pdf');

        self::save([
            '_meta' => [
                'version' => 1,
                'description' => 'Medical Certificate PDF field positions in millimeters (x/y from top-left of page).',
                'synced_at' => date('c'),
            ],
            'template' => $config['template'] ?? '',
            'x_offset' => $config['x_offset'] ?? 0,
            'y_offset' => $config['y_offset'] ?? 0,
            'page' => $config['page'] ?? [],
            'font' => $config['font'] ?? [],
            'page1' => $config['fields'] ?? [],
        ]);
    }

    public static function composePdfConfig(): array
    {
        $o = self::all();
        $base = config('med_cert_pdf');

        $page1 = array_replace_recursive($base['fields'] ?? [], $o['page1'] ?? []);

        return [
            'template' => $o['template'] ?? $base['template'],
            'font' => array_replace_recursive($base['font'] ?? [], $o['font'] ?? []),
            'page' => array_replace_recursive($base['page'] ?? [], $o['page'] ?? []),
            'x_offset' => (float)($o['x_offset'] ?? $base['x_offset'] ?? 0),
            'y_offset' => (float)($o['y_offset'] ?? $base['y_offset'] ?? 0),
            'fields' => $page1,
        ];
    }
}