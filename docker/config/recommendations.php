<?php

return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://internal_elife_dummy_api',
    'elastic_servers' => ['internal_elife_search_elasticsearch:9200'],
    'annotation_cache' => false,
    'ttl' => 0,
    'gearman_servers' => ['internal_elife_gearman'],
    'gearman_auto_restart' => true,
];
