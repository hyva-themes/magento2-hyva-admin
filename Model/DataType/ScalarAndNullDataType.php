<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

class ScalarAndNullDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_SCALAR_NULL = 'scalar_null';

    public function typeOf($value): ?string
    {
        return is_scalar($value) || is_null($value) ?
            self::TYPE_SCALAR_NULL :
            null;
    }

    public function toString($value): ?string
    {
        return $this->typeOf($value)
            ? (string) $value
            : null;
    }
}
