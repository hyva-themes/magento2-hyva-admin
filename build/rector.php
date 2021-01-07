<?php

declare(strict_types=1);

/*
 * This config is used with the dockerized rector 0.8.8 sometimes which uses the php downgrade constants on SetList.
 *
 * Newer versions use DowngradeSetList, but they currently are broken for downgrades.
 * In future, once they are fixed, I might decide to upgrade, so I'll leave the DowngradeSetList in here as a reference
 * until then.
 */

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$DOWNGRADE_PHP74 = class_exists(DowngradeSetList::class) && defined(DowngradeSetList::class . '::PHP_74')
    ? DowngradeSetList::PHP_74
    : SetList::DOWNGRADE_PHP74;

$DOWNGRADE_PHP73 = class_exists(DowngradeSetList::class) && defined(DowngradeSetList::class . '::PHP_73')
    ? DowngradeSetList::PHP_73
    : SetList::DOWNGRADE_PHP72; // there is no SetList::DOWNGRADE_PHP73, use 72 instead

return static function (ContainerConfigurator $containerConfigurator) use ($DOWNGRADE_PHP73, $DOWNGRADE_PHP74): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SETS, [
        SetList::DEAD_CODE,
        $DOWNGRADE_PHP74,
        $DOWNGRADE_PHP73
    ]);
};
