<?php

namespace App\Models;

use App\Observers\ThemeOptionObserver;
use Barryvdh\Debugbar\Twig\Extension\Debug;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ThemeOptionObserver::class])]
class ThemeOption extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'options' => 'json',
    ];

    /**
     * The Options that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'options',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getOptionsAttribute($value)
    {
        $values = json_decode($value, true);

        $headerLogo = Attachment::whereUuid($values['logo']['header_logo_id'])->first();
        $footerLogo = Attachment::whereUuid($values['logo']['footer_logo_id'])->first();
        $faviconIcon = Attachment::whereUuid($values['logo']['favicon_icon_id'])->first();
        //$seoOGImage = Attachment::whereUuid($values['seo']['og_image_id'])->first();

        $values['logo']['favicon_icon'] = $faviconIcon;
        $values['logo']['header_logo'] = $headerLogo;
        $values['logo']['footer_logo'] = $footerLogo;
        //$values['seo']['og_image'] = $seoOGImage;

        return $values;
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    /**
     * @return BelongsTo
     */
    public function front_site_logo(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'front_site_logo_id');
    }

    /**
     * @return BelongsTo
     */
    public function front_site_favicon(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'front_site_favicon_id');
    }

}
