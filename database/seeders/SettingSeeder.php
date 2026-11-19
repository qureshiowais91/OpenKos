<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Regional values come from config/regional.php — the single
        // source of truth; these rows just materialize the same values.
        $regional = config('regional');
        Setting::set('site_name', 'GrihaFlow');
        Setting::set('country_code', $regional['country']['code']);
        Setting::set('locale', $regional['locale']['app']);
        Setting::set('currency', $regional['currency']['code']);
        Setting::set('supported_currencies', $regional['currency']['supported']);
        Setting::set('timezone', $regional['timezone']);
        Setting::set('lease_id_prefix', 'LSX');
        Setting::set('invoice_id_prefix', 'INV');
    }
}
