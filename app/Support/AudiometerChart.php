<?php

namespace App\Support;

class AudiometerChart
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public static function frequencies(): array
    {
        return config('audiometer_chart.frequencies', []);
    }

    /**
     * @return array<string, array{x: float, y: float, w?: float}>
     */
    public static function positions(): array
    {
        return config('audiometer_chart.positions', []);
    }

    /**
     * @return list<string>
     */
    public static function formKeys(): array
    {
        $keys = [];
        foreach (self::positions() as $id => $_pos) {
            $keys[] = 'audiometer_'.$id;
        }

        return $keys;
    }
}