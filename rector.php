<?php

declare(strict_types=1);

use Cambis\SilverstripeRector\Set\ValueObject\SilverstripeLevelSetList;
use Cambis\SilverstripeRector\Set\ValueObject\SilverstripeSetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests/Src',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: false,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        naming: false,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withPhpSets(php83: true)
    ->withImportNames(importShortClasses: false)
    ->withSets([
        SilverstripeLevelSetList::UP_TO_SILVERSTRIPE_60,
        SilverstripeSetList::CODE_QUALITY,
    ]);
