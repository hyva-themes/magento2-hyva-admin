<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

interface HyvaGridConfigReaderInterface
{
    public function getGridConfiguration(string $gridName): array;
}
