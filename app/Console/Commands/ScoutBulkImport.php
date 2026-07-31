<?php

namespace App\Console\Commands;

use App\Models\Product;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class ScoutBulkImport extends Command
{
    protected $signature = 'scout:bulk-import
                            {--chunk=1000}
                            {--parallel=4}
                            {--disable-refresh}';
    protected $description = 'Bulk import using optimized eager loading, async, and ES tuning';

    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        $parallel = (int) $this->option('parallel');
        $disableRefresh = $this->option('disable-refresh');

        $hostsEnv = env('ELASTICSEARCH_HOSTS');
        $hostEnv = env('ELASTICSEARCH_HOST');
        $hosts = [];
        if ($hostsEnv) {
            // Support comma-separated ELASTICSEARCH_HOSTS
            $hosts = array_map('trim', explode(',', $hostsEnv));
        } elseif ($hostEnv) {
            $hosts = [trim($hostEnv)];
        } else {
            $hosts = ['http://localhost:9200'];
        }

        $builder = ClientBuilder::create();

        $cloudId = env('ELASTICSEARCH_CLOUD_ID');
        $user = env('ELASTICSEARCH_USER');
        $password = env('ELASTICSEARCH_PASSWORD');
        $apiKey = env('ELASTICSEARCH_API_KEY');
        $sslVerification = filter_var(env('ELASTICSEARCH_SSL_VERIFICATION', true), FILTER_VALIDATE_BOOL);

        if ($cloudId) {
            $builder->setElasticCloudId($cloudId);
        } else {
            $builder->setHosts($hosts);
        }

        if ($user && $password) {
            try {
                $builder->setBasicAuthentication($user, $password);
            } catch (\Throwable $e) {
                $this->warn('Failed to set basic authentication: ' . $e->getMessage());
            }
        } elseif ($apiKey) {
            try {
                // Accept either base64 or id:key formats depending on client support
                $builder->setApiKey($apiKey);
            } catch (\Throwable $e) {
                $this->warn('Failed to set API key authentication: ' . $e->getMessage());
            }
        }

        try {
            $builder->setSSLVerification($sslVerification);
        } catch (\Throwable $e) {
            // Some client versions may not support this method; ignore safely
        }

        $client = $builder->build();

        $indexName = (new Product())->searchableAs();

        // Ensure index exists; create if missing
        try {
            $existsResponse = $client->indices()->exists(['index' => $indexName]);
            $exists = method_exists($existsResponse, 'asBool')
                ? $existsResponse->asBool()
                : (method_exists($existsResponse, 'getStatusCode') && $existsResponse->getStatusCode() === 200);
        } catch (\Throwable $e) {
            $this->warn("Failed checking index existence: " . $e->getMessage());
            $exists = false;
        }

        if (!$exists) {
            try {
                $client->indices()->create([
                    'index' => $indexName,
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1,
                            'number_of_replicas' => $disableRefresh ? 0 : 1,
                            'refresh_interval' => $disableRefresh ? -1 : '1s',
                        ],
                        // Keep dynamic mapping to avoid strict failures
                        'mappings' => [
                            'dynamic' => true,
                            'properties' => [
                                'id' => ['type' => 'keyword'],
                                'name' => ['type' => 'text'],
                                'sku' => ['type' => 'keyword'],
                            ],
                        ],
                    ],
                ]);
                $this->info("Created index: {$indexName}");
            } catch (\Throwable $e) {
                $this->error("Failed to create index {$indexName}: " . $e->getMessage());
            }
        } elseif ($disableRefresh) {
            // Optimize Elasticsearch index settings for bulk imports
            try {
                $client->indices()->putSettings([
                    'index' => $indexName,
                    'body' => [
                        'settings' => [
                            'refresh_interval' => -1,
                            'number_of_replicas' => 0,
                        ]
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->warn("Failed to apply bulk import settings: " . $e->getMessage());
            }
        }

        $total = Product::count();
        $this->info("Total products: {$total}");

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $processed = 0;

        // Use chunkById for efficient database pagination
        Product::query()->chunkById($chunkSize, function ($products) use ($client, &$processed, $progress, $indexName) {
            // Eager load relationships for entire chunk
            $products->loadMissing([
                'variations',
                'product_thumbnail',
                'product_meta_image',
                'product_galleries',
                'attributes',
                'categories',
                'tags',
            ]);

            $params = ['body' => []];
            foreach ($products as $product) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $indexName,
                        '_id' => $product->id
                    ]
                ];
                $params['body'][] = $product->toSearchableArray();
            }

            // Execute bulk request synchronously
            if (!empty($params['body'])) {
                try {
                    $client->bulk($params);
                } catch (\Exception $e) {
                    $this->error("Error importing batch: " . $e->getMessage());
                }
            }

            $processed += count($products);
            $progress->advance(count($products));

            // Free memory
            unset($products, $params);
        }, $column = 'id');

        // Restore Elasticsearch settings if modified
        if ($disableRefresh) {
            try {
                $client->indices()->putSettings([
                    'index' => $indexName,
                    'body' => [
                        'settings' => [
                            'refresh_interval' => '1s',
                            'number_of_replicas' => 1,
                        ]
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->warn("Failed to restore index settings: " . $e->getMessage());
            }
        }

        $progress->finish();
        $this->info("\nImport completed. Processed: {$processed} records");
    }


}
