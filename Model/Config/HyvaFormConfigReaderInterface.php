<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

interface HyvaFormConfigReaderInterface
{
    public function getFormConfiguration(string $formName): array;
}
