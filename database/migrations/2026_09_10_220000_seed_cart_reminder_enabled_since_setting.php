<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Cart reminders must never treat pre-existing/historical cart rows as
 * "abandoned right now" — cart_reminder_first_hours (etc.) can only ever
 * apply to a cart session that started on/after this cutoff. Without it,
 * the very first run of cart:send-reminders would (and did) blast every
 * user who had ever left something in their cart, including years-old
 * rows left over from before carts were cleared on order placement.
 */
return new class extends Migration
{
    public function up(): void
    {
        $setting = Setting::first();
        if (!$setting) return;

        $values = $setting->getRawOriginal('values');
        $values = is_string($values) ? json_decode($values, true) : $values;

        if (!isset($values['cart_reminder_enabled_since'])) {
            $values['cart_reminder_enabled_since'] = now()->toDateTimeString();
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

        unset($values['cart_reminder_enabled_since']);
        $setting->setRawAttributes(['values' => json_encode($values)]);
        $setting->saveQuietly();
    }
};
