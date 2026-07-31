<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    protected string $logName   = 'default';
    protected string $event     = 'custom';
    protected string $description = '';
    protected ?Model $subject   = null;
    protected ?Model $causer    = null;
    protected array  $properties = [];

    public function useLog(string $name): static
    {
        $this->logName = $name;
        return $this;
    }

    public function event(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function on(Model $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function by(?Model $causer): static
    {
        $this->causer = $causer;
        return $this;
    }

    public function withProperties(array $props): static
    {
        $this->properties = $props;
        return $this;
    }

    public function withChanges(array $old, array $new): static
    {
        $this->properties = ['old' => $old, 'attributes' => $new];
        return $this;
    }

    public function log(string $description): ActivityLog
    {
        // Resolve actor: explicit causer > web session (admin) > API/Sanctum (customer)
        $causer = $this->causer
            ?? auth('web')->user()
            ?? auth('sanctum')->user();

        return ActivityLog::create([
            'log_name'     => $this->logName,
            'event'        => $this->event,
            'description'  => $description,
            'subject_type' => $this->subject ? get_class($this->subject) : null,
            'subject_id'   => $this->subject?->getKey(),
            'causer_type'  => $causer ? get_class($causer) : null,
            'causer_id'    => $causer?->getKey(),
            'properties'   => empty($this->properties) ? null : $this->properties,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
        ]);
    }

    // ─── Static factory ───────────────────────────────────────────

    public static function make(): static
    {
        return new static();
    }
}
