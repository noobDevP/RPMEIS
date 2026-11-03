<?php

namespace App\Support;

use InvalidArgumentException;

class StationRegistry
{
    public static function keys(): array
    {
        return array_keys(config('stations.stations', []));
    }

    public static function get(string $stationKey): array
    {
        $station = config("stations.stations.{$stationKey}");

        if (! is_array($station)) {
            throw new InvalidArgumentException("Unknown station [{$stationKey}].");
        }

        return $station;
    }

    public static function logSectionKey(string $stationKey): string
    {
        return match ($stationKey) {
            'optha_eent' => 'ophtha',
            'lab_drugtest' => 'lab_drugtest',
            default => $stationKey,
        };
    }

    public static function doctorEditableFields(string $stationKey): array
    {
        $station = self::get($stationKey);

        return [
            $station['diagnosis'],
            $station['remarks'],
            $station['approval'],
        ];
    }
}
