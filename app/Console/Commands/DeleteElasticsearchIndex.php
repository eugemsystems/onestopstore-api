<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class DeleteElasticsearchIndex extends Command
{
    protected $signature = 'es:delete-index {--force}';
    protected $description = 'Delete the Elasticsearch products index (use --force to confirm)';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error('This will DELETE the entire Elasticsearch index!');
            $this->error('Use --force flag to confirm: php artisan es:delete-index --force');
            return self::FAILURE;
        }

        $client = ElasticsearchService::client();
        $index = ElasticsearchService::indexName();

        try {
            // Check if index exists
            $existsResponse = $client->indices()->exists(['index' => $index]);
            $exists = method_exists($existsResponse, 'asBool')
                ? $existsResponse->asBool()
                : (method_exists($existsResponse, 'getStatusCode') && $existsResponse->getStatusCode() === 200);

            if (!$exists) {
                $this->info("Index '{$index}' does not exist. Nothing to delete.");
                return self::SUCCESS;
            }

            // Delete the index
            $this->warn("Deleting index: {$index}");
            $client->indices()->delete(['index' => $index]);

            $this->info("✓ Index '{$index}' deleted successfully!");
            $this->info("");
            $this->info("Next steps:");
            $this->info("1. Run: php artisan es:reindex-products --chunk=500");
            $this->info("2. This will recreate the index with correct mappings");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to delete index: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}

