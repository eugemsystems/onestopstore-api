<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\Psr18Client; // ← PSR-18 wrapper

class ElasticsearchService
{
    public static function client(): Client
    {
        $hostEnv  = config('elasticsearch.host', 'http://elasticsearch:9200');
        $hosts = array_map('trim', explode(',', $hostEnv));

        $sslVerify = filter_var(config('elasticsearch.ssl_verification', true), FILTER_VALIDATE_BOOL);

        $symfonyHttp = SymfonyHttpClient::create([
            'timeout'      => (int) env('ELASTICSEARCH_TIMEOUT', 300),
            'max_duration' => (int) env('ELASTICSEARCH_MAX_DURATION', 300),
            'headers'      => ['Connection' => 'keep-alive'],
            'verify_peer'  => $sslVerify,
            'verify_host'  => $sslVerify,
        ]);

        $psr18 = new Psr18Client($symfonyHttp);

        $builder = ClientBuilder::create()
            ->setHosts($hosts)
            ->setHttpClient($psr18);

        if ($cloudId = config('elasticsearch.cloud_id')) {
            $builder->setElasticCloudId($cloudId);
        }

        $user = config('elasticsearch.user');
        $pass = config('elasticsearch.password');
        $api  = config('elasticsearch.api_key');

        if ($user && $pass) {
            $builder->setBasicAuthentication($user, $pass);
        } elseif ($api) {
            $builder->setApiKey($api);
        }

        return $builder->build();
    }

    public static function indexName(): string
    {
        return config('elasticsearch.index', 'products_search');
    }
}
