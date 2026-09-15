<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/storage/cache/php-cs-fixer.cache')
    ->setFinder(PhpCsFixer\Finder::create()->in([
        __DIR__ . '/app', __DIR__ . '/bootstrap', __DIR__ . '/config',
        __DIR__ . '/routes', __DIR__ . '/database', __DIR__ . '/tests', __DIR__ . '/public',
    ])->notPath([
        '2026_07_10_000001_create_home_messages_table.php',
        '2026_07_15_000002_create_users_table.php',
    ])->append([__DIR__ . '/wps']))
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
    ]);
