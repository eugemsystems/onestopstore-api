<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Models\AuctionBidDeposit;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'branch',
        'phone',
        'status',
        'password',
        'is_approved',
        'country_code',
        'created_by_id',
        'system_reserve',
        'profile_image_id',
        'preferred_currency',
        'currency_symbol',
        'currency_exchange_rate',
        'membership_card_number',
        'card_assigned_at',
        'auction_approved',
    ];

    protected $guard_name = 'web';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'roles',
        'password',
        'permissions',
        'remember_token',
        'deleted_at',
        'updated_at',
    ];

    protected $with = [
        'profile_image'
    ];

    protected $withCount = [
        'orders'
    ];

    protected $casts = [
        'phone' => 'integer',
        'status' => 'integer',
        'orders_count' => 'integer',
        'created_by_id' => 'integer',
        'email_verified_at' => 'datetime',
        'card_assigned_at' => 'datetime',
        'auction_approved' => 'boolean',
    ];

    protected $appends = [
        'role',
    ];

    public static function booted_old()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->created_by_id = Helpers::isUserLogin() ? Helpers::getCurrentUserId() : $model->id;
        });
        static::deleted(function($user) {
            $user->orders()->delete();
            $user->store()->delete();
            $user->reviews()->delete();
        });
    }

    public static function booted()
    {
        parent::boot();

        // Before save: set created_by_id
        static::saving(function ($model) {
            $model->created_by_id = Helpers::isUserLogin()
                ? Helpers::getCurrentUserId()
                : $model->id;
        });

        // After create/update: flush search cache
        static::saved(function ($user) {
            Cache::tags(['users','search'])->flush();
        });

        // After delete: handle related deletions + flush cache
        static::deleted(function ($user) {
            $user->orders()->delete();
            $user->store()->delete();
            $user->reviews()->delete();

            Cache::tags(['users','search'])->flush();
        });

        static::forceDeleted(function ($user) {
            Cache::tags(['users','search'])->flush();
        });
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('user')?->id;
    }

    /**
     * @return BelongsTo
     */
    public function profile_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'profile_image_id');
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'consumer_id');
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'consumer_id');
    }

    /**
     * @return HasMany
     */
    public function store(): HasMany
    {
        return $this->hasMany(Store::class, 'vendor_id');
    }

    /**
     * @return HasMany
     */
    public function address(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    /**
     * @return hasOne
     */
    public function payment_account(): hasOne
    {
        return $this->hasOne(PaymentAccount::class, 'user_id');
    }

    /**
     * @return HasOne
     */
    public function point(): HasOne
    {
        return $this->hasOne(Point::class, 'consumer_id');
    }

    /**
     * @return HasOne
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'consumer_id');
    }

    /**
     * @return HasOne
     */
    public function points(): HasOne
    {
        return $this->hasOne(Point::class, 'consumer_id');
    }

    /**
     * @return HasOne
     */
    public function vendor_wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class, 'vendor_id');
    }

    /**
     * Get the user who created blog.
     * @return HasMany
     */
    public function created_by(): HasMany
    {
        return $this->hasMany(Blog::class, 'created_by');
    }

    /**
     * @return HasMany
     */
    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'consumer_id');
    }

    /**
     * @return HasMany
     */
    public function withdraw_request(): HasMany
    {
        return $this->hasMany(WithdrawRequest::class, 'vendor_id');
    }

    /**
     * @return HasMany
     */
    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'consumer_id');
    }

    /**
     * @return HasMany
     */
    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * @return HasMany
     */
    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'user_id');
    }

    /**
     * Auction bid deposits placed by this user.
     * @return HasMany
     */
    public function auctionDeposits(): HasMany
    {
        return $this->hasMany(AuctionBidDeposit::class, 'user_id');
    }

    /**
     * Get the user's all permissions.
     */
    public function getPermissionAttribute()
    {
        return $this->getAllPermissions();
    }

    /**
     * Get the user's role.
     */
    public function getRoleAttribute()
    {
        return $this->roles->first()?->makeHidden(['created_at', 'updated_at','pivot']);
    }

    /**
     * Get the Store attributes as a admin or vendor.
     */
    public function getStoreAttribute()
    {
        if ($this->hasRole(RoleEnum::VENDOR)) {
            return $this->store()->first();
        }
    }

    /**
     * Route Slack notifications for admin users to the app alerts webhook.
     */
    public function routeNotificationForSlack($notification)
    {
        if ($this->hasRole(RoleEnum::ADMIN)) {
            return config('slack.slack_webhook_url_app_alerts');
        }
        return null;
    }

    /**
     * Route WhatsApp notifications - get user's phone number with country code
     */
    public function routeNotificationForWhatsApp($notification = null)
    {
        // Return phone number with country code
        // MSG91 expects format: 918888888888 (country code + number without +)
        if (!empty($this->phone)) {
            $countryCode = $this->country_code ?? '27'; // Default to South Africa
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            $countryCode = preg_replace('/[^0-9]/', '', $countryCode);

            // If phone doesn't start with country code, prepend it
            if (!str_starts_with($phone, $countryCode)) {
                return $countryCode . $phone;
            }

            return $phone;
        }

        return null;
    }

    // Mutators to normalize email and name
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim((string) $value));
    }

    public function setNameAttribute($value)
    {
        $normalized = preg_replace('/\s+/', ' ', trim((string) $value));
        $this->attributes['name'] = Str::title(Str::lower($normalized));
    }

    // Query scope for case-insensitive email lookups
    public function scopeWhereEmail($query, $email)
    {
        return $query->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $email))]);
    }

    /**
     * Add points to user's balance
     *
     * @param int $points Amount of points to add
     * @param string|null $reason Reason for adding points
     * @return bool
     */
    public function addPoints(int $points, ?string $reason = null): bool
    {
        // Get or create points record
        $pointsRecord = $this->points()->firstOrCreate(
            ['consumer_id' => $this->id],
            ['balance' => 0]
        );

        // Add points to balance
        $pointsRecord->balance += $points;
        $pointsRecord->save();

        // Log the transaction if you have a points history table
        // PointTransaction::create([...]);

        return true;
    }
}
