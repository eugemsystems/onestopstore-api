<?php

namespace App\Console\Commands;

use App\Models\LaybyApplication;
use App\Notifications\LaybyIdDocumentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLaybyDocumentReminders extends Command
{
    protected $signature   = 'layby:send-document-reminders {--dry-run : Preview without sending}';
    protected $description = 'Send reminder emails to layby applicants who have not uploaded their ID document';

    /** Minimum hours between reminders */
    private const REMINDER_INTERVAL_HOURS = 48;

    /** Maximum reminders to send before stopping */
    private const MAX_REMINDERS = 5;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $query = LaybyApplication::with('user')
            ->whereIn('status', ['pending', 'under_review'])
            ->whereNull('id_document_attachment_id')
            ->where(function ($q) {
                $q->whereNull('id_document_path')
                  ->orWhere('id_document_path', '');
            })
            ->where('id_document_reminder_count', '<', self::MAX_REMINDERS)
            ->where(function ($q) {
                // Never been reminded OR last reminder was > 48h ago
                $q->whereNull('id_document_last_reminder_at')
                  ->orWhere('id_document_last_reminder_at', '<=', now()->subHours(self::REMINDER_INTERVAL_HOURS));
            });

        $applications = $query->get();

        $this->info("Found {$applications->count()} application(s) eligible for a reminder.");

        if ($applications->isEmpty()) {
            return self::SUCCESS;
        }

        $sent   = 0;
        $errors = 0;

        foreach ($applications as $application) {
            $user = $application->user;

            if (!$user || !$user->email) {
                $this->warn("  ⚠  Application #{$application->id}: user or email missing, skipping.");
                continue;
            }

            // Increment count BEFORE sending so the notification can display the right number
            $newCount = $application->id_document_reminder_count + 1;

            if (!$dryRun) {
                $application->update([
                    'id_document_reminder_count'   => $newCount,
                    'id_document_last_reminder_at' => now(),
                ]);
            }

            $action = $dryRun ? '[DRY RUN]' : '';
            $this->line("  {$action} Sending reminder #{$newCount} to {$user->email} — App {$application->application_number}");

            if (!$dryRun) {
                try {
                    // Refresh so notification sees the updated count
                    $user->notify(new LaybyIdDocumentReminderNotification($application->fresh()));
                    $sent++;

                    if ($newCount >= self::MAX_REMINDERS) {
                        $this->warn("  ⚠  Max reminders reached for {$application->application_number} — flagged for manual follow-up.");
                        Log::warning('Layby document reminder limit reached', [
                            'application_id'     => $application->id,
                            'application_number' => $application->application_number,
                            'user_email'         => $user->email,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('Failed to send layby document reminder', [
                        'application_id' => $application->id,
                        'user_email'     => $user->email,
                        'error'          => $e->getMessage(),
                    ]);
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                }
            }
        }

        $this->info("Done. Sent: {$sent} | Errors: {$errors}");

        return self::SUCCESS;
    }
}
