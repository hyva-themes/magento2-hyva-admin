<?php

declare(strict_types=1);

/*
 * This rector config file is used with the rector 0.8.8 docker image.
 * Newer I've tried versions don't work. Once a newer docker image is functional, I might
 * refactor the config for that newer version.
 * For that reason the DowngradeSetList import that is used by newer versions still are present as comments
 * (The version 0.8.8 uses SetList for PHP downgrades).
 *
 * Currently testing if current stable can be installed with M2 on github actions...
 */

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        SetList::DEAD_CODE,
        DowngradeSetList::PHP_74,
        DowngradeSetList::PHP_73,
        //SetList::DOWNGRADE_PHP74,
        //SetList::DOWNGRADE_PHP72, // there is no SetList::DOWNGRADE_PHP73, use 72 instead
    ]);
};
