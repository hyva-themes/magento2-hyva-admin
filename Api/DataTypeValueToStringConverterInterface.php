<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

interface DataTypeValueToStringConverterInterface
{
    public function toString($value): ?string;
}
