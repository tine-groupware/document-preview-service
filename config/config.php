<?php

use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

$aggregator = new ConfigAggregator([
    new ArrayProvider($cacheConfig),

    Auth\ConfigProvider::class,

    DocumentService\ConfigProvider::class,

    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),

    new PhpFileProvider(getConfigPath()),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();


function getConfigPath(){
    $conf = getenv('documentpreviewconfigVERSION');
    if (!($conf == false || $conf == '')){
        if (is_file($conf)) return $conf;
        $conf .= 'config.php';
        if (is_file($conf)) return $conf;
    }
    return '/etc/documentPreviewService/VERSION/config.php';
}