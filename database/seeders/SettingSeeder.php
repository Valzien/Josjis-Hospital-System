<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Pengaturan sistem default.
 *
 * Kunci di sini harus sama persis dengan daftar FIELDS pada
 * App\Http\Controllers\Admin\SettingController agar seluruh field
 * Pengaturan terisi nilai awal dan dapat disimpan tanpa error.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Umum
            ['key' => 'hospital_name', 'value' => 'JOSJIS Hospital System'],
            ['key' => 'hospital_tagline', 'value' => 'Hospital Information System'],
            ['key' => 'hospital_address', 'value' => 'Jl. Prototype No. 00, Kota Simulasi'],
            ['key' => 'hospital_phone', 'value' => '(021) 000-0000'],
            ['key' => 'hospital_email', 'value' => 'info@josjis-hospital.test'],

            // Antrean
            ['key' => 'queue_prefix', 'value' => 'A'],
            ['key' => 'estimated_minutes', 'value' => '10'],
            ['key' => 'auto_call_refresh', 'value' => '30'],

            // Sistem
            ['key' => 'demo_mode', 'value' => '1'],
            ['key' => 'allow_patient_registration', 'value' => '1'],
            ['key' => 'allow_online_queue', 'value' => '1'],

            // Farmasi
            ['key' => 'low_stock_alert', 'value' => '1'],
        ];

        foreach ($settings as $setting) {
            if (Setting::query()->where('key', $setting['key'])->exists()) {
                continue;
            }

            Setting::put($setting['key'], $setting['value'], 'umum');
        }
    }
}
