<?php

namespace App\Traits;

use App\Services\ActivityLogger;

/**
 * LogsActivity
 *
 * Add this trait to any Eloquent model to automatically log
 * create / update / delete events performed by an authenticated
 * admin / staff user.
 *
 * Only fires when:
 *   - A web-session user is authenticated
 *   - That user is NOT a plain consumer
 *   - The static $skipLogging flag is false (set true during bulk imports)
 *
 * Skips noisy "housekeeping" columns that change constantly:
 *   updated_at, last_seen_at, remember_token, email_verified_at
 */
trait LogsActivity
{
    /** Set to true during fast/bulk imports to suppress per-row logging */
    public static bool $skipLogging = false;

    /** Columns to ignore in the diff (too noisy / not meaningful) */
    protected static array $auditIgnore = [
        'updated_at', 'last_seen_at', 'remember_token',
        'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public static function bootLogsActivity(): void
    {
        // ── created ────────────────────────────────────────────────
        static::created(function ($model) {
            if (!static::shouldAudit()) return;

            try {
                ActivityLogger::make()
                    ->useLog(static::auditLogName($model))
                    ->event('created')
                    ->on($model)
                    ->log(static::auditLabel($model) . ' created');
            } catch (\Throwable) {}
        });

        // ── updated ────────────────────────────────────────────────
        static::updated(function ($model) {
            if (!static::shouldAudit()) return;

            $dirty = array_diff_key(
                $model->getDirty(),
                array_flip(array_merge(static::$auditIgnore, $model->auditIgnoreExtra ?? []))
            );

            if (empty($dirty)) return;

            try {
                $old = array_intersect_key($model->getOriginal(), $dirty);
                ActivityLogger::make()
                    ->useLog(static::auditLogName($model))
                    ->event('updated')
                    ->on($model)
                    ->withChanges($old, $dirty)
                    ->log(static::auditLabel($model) . ' updated: ' . implode(', ', array_keys($dirty)));
            } catch (\Throwable) {}
        });

        // ── deleted ────────────────────────────────────────────────
        static::deleted(function ($model) {
            if (!static::shouldAudit()) return;

            try {
                ActivityLogger::make()
                    ->useLog(static::auditLogName($model))
                    ->event('deleted')
                    ->on($model)
                    ->log(static::auditLabel($model) . ' deleted');
            } catch (\Throwable) {}
        });
    }

    // ── Helpers ────────────────────────────────────────────────────

    protected static function shouldAudit(): bool
    {
        if (static::$skipLogging) return false;

        $user = auth('web')->user();
        if (!$user) return false;

        // Skip consumers — only log admin / staff actions
        if (method_exists($user, 'hasRole') && $user->hasRole('consumer')) return false;

        return true;
    }

    protected static function auditLogName($model): string
    {
        // Allow model to define its own log name via $auditLogName property
        return $model->auditLogName ?? strtolower(class_basename($model));
    }

    protected static function auditLabel($model): string
    {
        // Allow model to define its own display label via $auditLabel property
        if (isset($model->auditLabel)) return $model->auditLabel;

        // Auto-build from common name fields
        $name = $model->name ?? $model->title ?? $model->subject ?? $model->order_number ?? null;
        $id   = $model->getKey();

        return $name
            ? class_basename($model) . " '{$name}' (#{$id})"
            : class_basename($model) . " #{$id}";
    }
}
