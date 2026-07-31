<?php

namespace App\Models;

use App\Observers\SettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([SettingObserver::class])]
class Setting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'values' => 'json',
    ];

    /**
     * The values that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'values',
    ];

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('settings');
    }

    public function getValuesAttribute($value)
    {
        $values = is_string($value) ? json_decode($value, true) : $value;

        $lightLogoImage = Attachment::whereUuid($values['general']['light_logo_image_id'])->first();
        $darkLogoImage = Attachment::whereUuid($values['general']['dark_logo_image_id'])->first();
        $faviconImage = Attachment::whereUuid($values['general']['favicon_image_id'])->first();
        $tinyImage = Attachment::whereUuid($values['general']['tiny_logo_image_id'])->first();
        $defaultCurrency = Currency::find($values['general']['default_currency_id']);
        $maintenanceImage = Attachment::whereUuid($values['maintenance']['maintenance_image_id'])->first();

        $values['general']['light_logo_image'] = $lightLogoImage;
        $values['general']['dark_logo_image'] = $darkLogoImage;
        $values['general']['favicon_image'] = $faviconImage;
        $values['general']['tiny_logo_image'] = $tinyImage;
        $values['general']['default_currency'] = $defaultCurrency;
        $values['maintenance']['maintenance_image'] = $maintenanceImage;

        return $values;
    }

    public function setValuesAttribute($value)
    {
        $this->attributes['values'] = json_encode($value);
    }
}
