<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'import_type',
        'filename',
        'status',
        'error_message',
        'total_items',
        'processed_items',
        'updated_items',
        'skipped_items',
        'failed_items',
        'skip_reasons',
        'percentage',
        'todays_rate',
        'started_at',
        'completed_at',
        'duration_seconds',
        'user_id',
    ];

    protected $casts = [
        'skip_reasons' => 'array',
        'percentage' => 'decimal:4',
        'todays_rate' => 'decimal:6',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'updated_items' => 'integer',
        'skipped_items' => 'integer',
        'failed_items' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the user who initiated the import
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get jobs for a specific batch
     */
    public function scopeForBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to get pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get jobs by import type
     */
    public function scopeByImportType($query, string $importType)
    {
        return $query->where('import_type', $importType);
    }

    /**
     * Scope to get fast-import jobs only
     */
    public function scopeFastImport($query)
    {
        return $query->where('import_type', 'fast-import');
    }

    /**
     * Scope to get fast-import-update jobs only
     */
    public function scopeFastImportUpdate($query)
    {
        return $query->where('import_type', 'fast-import-update');
    }

    /**
     * Check if the job can be retried
     */
    public function canRetry(): bool
    {
        return in_array($this->status, ['failed', 'pending']);
    }

    /**
     * Mark the job as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Refresh to ensure started_at is loaded
        $this->refresh();
    }

    /**
     * Mark the job as completed
     */
    public function markAsCompleted(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at ?? $this->created_at ?? $completedAt;

        // Calculate duration, ensuring it's always positive
        $duration = abs($completedAt->diffInSeconds($startedAt, false));

        $this->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_seconds' => (int) $duration,
        ]);
    }

    /**
     * Mark the job as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $completedAt = now();
        $startedAt = $this->started_at ?? $this->created_at ?? $completedAt;

        // Calculate duration, ensuring it's always positive
        $duration = abs($completedAt->diffInSeconds($startedAt, false));

        // Truncate error message to prevent database issues (limit to 5000 chars)
        $truncatedError = strlen($errorMessage) > 5000
            ? substr($errorMessage, 0, 5000) . '... (truncated)'
            : $errorMessage;

        $this->update([
            'status' => 'failed',
            'error_message' => $truncatedError,
            'completed_at' => $completedAt,
            'duration_seconds' => (int) $duration,
        ]);
    }

    /**
     * Update progress statistics
     */
    public function updateProgress(
        int $processedItems,
        int $updatedItems,
        int $skippedItems,
        array $skipReasons = []
    ): void {
        $this->update([
            'processed_items' => $processedItems,
            'updated_items' => $updatedItems,
            'skipped_items' => $skippedItems,
            'skip_reasons' => $skipReasons,
        ]);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return round(($this->processed_items / $this->total_items) * 100, 2);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}

