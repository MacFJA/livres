<?php

$projectPath = __DIR__ . '/';

$scanDirectories = [
    $projectPath . '/src/',
];

$scanFiles = [
    $projectPath.'/config/bundles.php'
];
$excludeDirectories = [
    'Svelte',
];
return [
    /**
     * Required params
     **/
    'composerJsonPath' => $projectPath . '/composer.json',
    'vendorPath' => $projectPath . '/vendor/',
    'scanDirectories' => $scanDirectories,

    /**
     * Optional params
     **/
    'skipPackages' => [
        'symfony/flex',// Composer plugin
        'mlocati/composer-patcher',// Composer plugin
        'symfony/security-bundle',
        'symfony/yaml',
        'nyholm/psr7',// Service provider
        'php-http/curl-client',// Service provider
        'predis/predis',// Service provider
        'symfony/dotenv',
        'symfony/form'
    ],
    'excludeDirectories' => $excludeDirectories,
    'scanFiles' => $scanFiles,
    'extensions' => ['*.php'],
    'requireDev' => false
];