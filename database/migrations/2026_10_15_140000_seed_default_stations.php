<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $stationsList = [
            ['station_key' => 'gpe', 'station_name' => 'General Physical Examination', 'sort_order' => 1],
            ['station_key' => 'dental', 'station_name' => 'Dental', 'sort_order' => 2],
            ['station_key' => 'optha_eent', 'station_name' => 'Optha / EENT', 'sort_order' => 3],
            ['station_key' => 'ecg_xray', 'station_name' => 'ECG / X-Ray', 'sort_order' => 4],
            ['station_key' => 'lab_drugtest', 'station_name' => 'Laboratory / Drug Test', 'sort_order' => 5],
            ['station_key' => 'np', 'station_name' => 'Neuro-Psychiatric', 'sort_order' => 6],
        ];

        foreach ($stationsList as $s) {
            $existing = DB::table('stations')->where('station_key', $s['station_key'])->first();
            if (!$existing) {
                DB::table('stations')->insert(array_merge($s, [
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // Keep stations on rollback to prevent data loss
    }
};
