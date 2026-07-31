<?php

namespace App\Console\Commands;

use App\Models\LaybyApplication;
use Illuminate\Console\Command;

class RecalculateLaybyTotals extends Command
{
    protected $signature = 'layby:recalculate-totals {application_id?}';
    protected $description = 'Recalculate layby application totals from completed payments';

    public function handle()
    {
        $applicationId = $this->argument('application_id');

        if ($applicationId) {
            // Recalculate single application
            $application = LaybyApplication::with('payments')->findOrFail($applicationId);
            $this->recalculateApplication($application);
        } else {
            // Recalculate all applications
            $applications = LaybyApplication::with('payments')->get();

            $this->info("Recalculating totals for {$applications->count()} layby applications...");

            foreach ($applications as $application) {
                $this->recalculateApplication($application);
            }

            $this->info("Done!");
        }
    }

    private function recalculateApplication($application)
    {
        // Get all completed payments
        $completedPayments = $application->payments()
            ->where('payment_status', 'completed')
            ->get();

        $oldTotalPaid = $application->total_paid;
        $newTotalPaid = $completedPayments->sum('amount');
        $newBalance = $application->total_amount - $newTotalPaid;

        $this->line("Application #{$application->id} ({$application->application_number}):");
        $this->line("  Old total_paid: {$oldTotalPaid}");
        $this->line("  Completed payments count: {$completedPayments->count()}");
        $this->line("  Calculated total_paid: {$newTotalPaid}");
        $this->line("  New balance_remaining: {$newBalance}");

        if ($oldTotalPaid != $newTotalPaid) {
            $this->warn("  ⚠️  Mismatch detected! Updating...");

            $application->total_paid = $newTotalPaid;
            $application->balance_remaining = $newBalance;

            // Update status if needed
            if ($application->status === 'pending' || $application->status === 'approved') {
                if ($newTotalPaid > 0) {
                    $application->status = 'active';
                    $this->line("  Status updated: approved → active");
                }
            }

            if ($newBalance <= 0.01) {
                $application->status = 'completed';
                $application->completed_at = $application->completed_at ?? now();
                $this->line("  Status updated: → completed");
            }

            $application->save();
            $this->info("  ✓ Updated!");
        } else {
            $this->info("  ✓ Totals correct");
        }

        $this->line("");
    }
}

