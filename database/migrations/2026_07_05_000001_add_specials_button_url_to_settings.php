<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $setting = Setting::first();
        if (!$setting) return;

        $values = $setting->getRawOriginal('values');
        $values = is_string($values) ? json_decode($values, true) : $values;

        if (!isset($values['general']['specials_button_url'])) {
            $values['general']['specials_button_url'] = '';
            $setting->setRawAttributes(['values' => json_encode($values)]);
            $setting->saveQuietly();
        }
    }

    public function down(): void
    {
        $setting = Setting::first();
        if (!$setting) return;

        $values = $setting->getRawOriginal('values');
        $values = is_string($values) ? json_decode($values, true) : $values;

        unset($values['general']['specials_button_url']);
        $setting->setRawAttributes(['values' => json_encode($values)]);
        $setting->saveQuietly();
    }
};
