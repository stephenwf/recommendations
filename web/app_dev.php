<?php

require_once __DIR__.'/bootstrap.php';

use eLife\App\Kernel;

$config = include __DIR__.'/../config/dev.php';

$kernel = new Kernel($config);

$kernel->withApp(function ($app) use ($config) {
    $app['debug'] = $config['debug'] ?? false;
});

$kernel->run();
