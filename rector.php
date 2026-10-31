<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    // 1. Tell Rector which folders to scan and modernize
    ->withPaths([
        __DIR__ . '/application',
		__DIR__ . '/Zend',
    ])
    // 2. Ignore vendor, tests, and bundled legacy libraries
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/tests',

        // Skip bundled third-party libraries that cause parsing errors:
        __DIR__ . '/application/modules/default/library/MPDF57',
        __DIR__ . '/application/modules/default/library/HTMLPurifier',
    ])
    // 3. Modernize syntax up to PHP 8.3
    ->withPhpSets(
        php83: true
    );