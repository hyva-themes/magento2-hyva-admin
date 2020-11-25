<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

interface DataTypeToStringConverterLocatorInterface
{
    public function forTypeCode(string $typeCode): ?DataTypeValueToStringConverterInterface;
}
