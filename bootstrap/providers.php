<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    Matchish\ScoutElasticSearch\ElasticSearchServiceProvider::class,
    Webpatser\Countries\CountriesServiceProvider::class,
];
