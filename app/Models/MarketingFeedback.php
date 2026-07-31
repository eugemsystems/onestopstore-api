<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingFeedback extends Model
{
    protected $table = 'marketing_feedback';

    protected $fillable = [
        'user_id',
        'order_number',
        'order_id',
        'ordering_process_rating',
        'heard_about_source',
        'heard_about_other',
        'user_name',
        'user_email',
        'user_phone',
        'additional_comments',
        'ip_address',
        'user_agent',
        'country_code',
        'country_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who submitted the feedback
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the feedback
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the rating label
     */
    public function getRatingLabelAttribute(): string
    {
        return match($this->ordering_process_rating) {
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            default => 'Unknown',
        };
    }

    /**
     * Get the source label
     */
    public function getSourceLabelAttribute(): string
    {
        if ($this->heard_about_source === 'other') {
            return $this->heard_about_other ?? 'Other';
        }

        return match($this->heard_about_source) {
            'google_adverts' => 'Google Adverts',
            'facebook_adverts' => 'Facebook Adverts',
            'instagram_promotion' => 'Instagram Promotion',
            'comic_awards' => 'Comic Awards',
            'dare_remachinda' => 'Dare Remachinda',
            'zimcelebs' => 'ZimCelebs',
            'tiktok_advert' => 'Tiktok Advert',
            'refered_by_friend' => 'Referred by a Friend',
            default => ucwords(str_replace('_', ' ', $this->heard_about_source)),
        };
    }

    /**
     * Get the device type from user agent
     */
    public function getDeviceTypeAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }

        $userAgent = strtolower($this->user_agent);

        // Check for mobile devices
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            if (preg_match('/iphone/i', $userAgent)) {
                return 'iPhone';
            } elseif (preg_match('/ipad/i', $userAgent)) {
                return 'iPad';
            } elseif (preg_match('/android/i', $userAgent)) {
                if (preg_match('/mobile/i', $userAgent)) {
                    return 'Android Phone';
                } else {
                    return 'Android Tablet';
                }
            } elseif (preg_match('/blackberry/i', $userAgent)) {
                return 'BlackBerry';
            } elseif (preg_match('/windows phone/i', $userAgent)) {
                return 'Windows Phone';
            } else {
                return 'Mobile Device';
            }
        }

        // Check for tablets
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'Tablet';
        }

        // Check for desktop operating systems
        if (preg_match('/windows/i', $userAgent)) {
            return 'Windows PC';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'Mac';
        } elseif (preg_match('/linux/i', $userAgent)) {
            return 'Linux PC';
        }

        // Default to Desktop
        return 'Desktop';
    }

    /**
     * Get the device icon class
     */
    public function getDeviceIconAttribute(): string
    {
        $device = $this->device_type;

        return match(true) {
            str_contains($device, 'iPhone') => 'bi-phone',
            str_contains($device, 'iPad') => 'bi-tablet',
            str_contains($device, 'Android Phone') => 'bi-phone',
            str_contains($device, 'Android Tablet') => 'bi-tablet',
            str_contains($device, 'Tablet') => 'bi-tablet',
            str_contains($device, 'Windows PC') => 'bi-laptop',
            str_contains($device, 'Mac') => 'bi-laptop-fill',
            str_contains($device, 'Linux') => 'bi-laptop',
            str_contains($device, 'Mobile') => 'bi-phone',
            default => 'bi-pc-display',
        };
    }
}

