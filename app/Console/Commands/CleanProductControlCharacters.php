<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CleanProductControlCharacters extends Command
{
    protected $signature = 'products:clean-control-chars {--dry-run : Preview changes without saving}';
    protected $description = 'Remove control characters from product names and descriptions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
        }

        $this->info('Starting control character cleanup...');
        $this->newLine();

        $totalProducts = Product::count();
        $this->info("📊 Total products: {$totalProducts}");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $cleaned = 0;
        $errors = 0;

        Product::chunk(500, function ($products) use (&$cleaned, &$errors, $bar, $dryRun) {
            foreach ($products as $product) {
                $bar->setMessage("Processing: {$product->name}");
                $bar->advance();

                $originalName = $product->name;
                $originalDescription = $product->description;
                $originalShortDescription = $product->short_description;

                // Clean the fields
                $cleanName = $this->removeControlCharacters($originalName);
                $cleanDescription = $this->removeControlCharacters($originalDescription);
                $cleanShortDescription = $this->removeControlCharacters($originalShortDescription);

                // Check if anything changed
                $hasChanges = false;
                if ($originalName !== $cleanName) {
                    $hasChanges = true;
                }
                if ($originalDescription !== $cleanDescription) {
                    $hasChanges = true;
                }
                if ($originalShortDescription !== $cleanShortDescription) {
                    $hasChanges = true;
                }

                if ($hasChanges) {
                    $cleaned++;

                    if (!$dryRun) {
                        try {
                            $product->name = $cleanName;
                            $product->description = $cleanDescription;
                            $product->short_description = $cleanShortDescription;
                            $product->save();
                        } catch (\Exception $e) {
                            $errors++;
                            $this->newLine();
                            $this->error("Error cleaning product ID {$product->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Cleanup completed!');
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total products processed', $totalProducts],
                ['Products with control characters', $cleaned],
                ['Errors', $errors],
                ['Clean products', $totalProducts - $cleaned],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No changes were saved.');
            $this->info('Run without --dry-run to actually clean the data:');
            $this->line('  php artisan products:clean-control-chars');
        } else {
            $this->newLine();
            $this->info('✨ Database cleaned successfully!');
        }

        return 0;
    }

    /**
     * Remove all non-printable control characters
     */
    private function removeControlCharacters($string)
    {
        if ($string === null || $string === '') {
            return '';
        }

        // Remove all control characters except tab (0x09), newline (0x0A), and carriage return (0x0D)
        // Control characters are 0x00-0x1F and 0x7F-0x9F
        $string = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/u', '', $string);

        // Also remove any invalid UTF-8 sequences
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove any remaining whitespace control characters
        $string = preg_replace('/\p{C}/u', '', $string);

        return $string;
    }
}

