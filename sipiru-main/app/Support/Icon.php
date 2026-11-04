<?php

namespace App\Support;

/** Ikon Lucide (ISC) disajikan sebagai SVG inline dari resources/icons. */
final class Icon
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function svg(string $name, string $class = 'size-5'): string
    {
        $svg = self::$cache[$name] ??= self::load($name);

        return str_replace('class="lucide"', 'class="'.e($class).' shrink-0" aria-hidden="true"', $svg);
    }

    private static function load(string $name): string
    {
        $path = resource_path('icons/'.basename($name).'.svg');
        if (! is_file($path)) {
            return '';
        }

        $svg = preg_replace('/<!--.*?-->/s', '', file_get_contents($path));
        $svg = preg_replace('/\s*(width|height)="24"/', '', $svg);
        $svg = preg_replace('/class="[^"]*"/', 'class="lucide"', $svg, 1);

        return trim(preg_replace('/\s+/', ' ', $svg));
    }
}
