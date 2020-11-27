<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class GenericObjectDataType implements DataTypeInterface
{
    const TYPE_GENERIC_OBJECT = 'object';

    public function valueToTypeCode($value): ?string
    {
        return is_object($value)
            ? self::TYPE_GENERIC_OBJECT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_GENERIC_OBJECT
            ? self::TYPE_GENERIC_OBJECT
            : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? sprintf('#(%s)', get_class($value))
            : null;
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
