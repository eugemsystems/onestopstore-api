<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class CheckElasticsearch extends Command
{
    protected $signature = 'es:check';
    protected $description = 'Check Elasticsearch connection and index status';

    public function handle(): int
    {
        try {
            $client = ElasticsearchService::client();
            $index = ElasticsearchService::indexName();

            $this->info("Checking Elasticsearch status...");
            $this->info("Index name: {$index}");

            // Check if index exists
            $exists = $client->indices()->exists(['index' => $index]);
            $indexExists = method_exists($exists, 'asBool') ? $exists->asBool() : ($exists->getStatusCode() === 200);

            if (!$indexExists) {
                $this->error("Index '{$index}' does not exist!");
                return self::FAILURE;
            }

            $this->info("✓ Index exists");

            // Get document count
            $countResponse = $client->count(['index' => $index]);
            $count = $countResponse['count'] ?? 0;
            $this->info("✓ Total documents: {$count}");

            // Get index stats
            $stats = $client->indices()->stats(['index' => $index]);
            $primaries = $stats['indices'][$index]['primaries'] ?? [];
            $docs = $primaries['docs'] ?? [];

            $this->info("✓ Index size: " . ($primaries['store']['size_in_bytes'] ?? 0) / 1024 / 1024 . " MB");
            $this->info("✓ Documents count: " . ($docs['count'] ?? 0));
            $this->info("✓ Deleted docs: " . ($docs['deleted'] ?? 0));

            // Try a simple search
            $searchResponse = $client->search([
                'index' => $index,
                'body' => [
                    'query' => ['match_all' => (object)[]],
                    'size' => 1
                ]
            ]);

            $hits = $searchResponse['hits']['total']['value'] ?? 0;
            $this->info("✓ Search test: {$hits} total hits");

            if ($hits > 0) {
                $firstDoc = $searchResponse['hits']['hits'][0]['_source'] ?? [];
                $this->info("✓ First document ID: " . ($firstDoc['id'] ?? 'N/A'));
                $this->info("✓ First document name: " . ($firstDoc['name'] ?? 'N/A'));
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Elasticsearch error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}

