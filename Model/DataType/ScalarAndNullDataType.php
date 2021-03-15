<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class ScalarAndNullDataType implements DataTypeInterface
{
    public const TYPE_SCALAR_NULL = 'scalar_null';

    private const SCALAR_TYPES = [
        'int',
        'float',
        'decimal',
        'bool',
        'static',
        self::TYPE_SCALAR_NULL
    ];

    public function valueToTypeCode($value): ?string
    {
        return is_scalar($value) || is_null($value) ?
            self::TYPE_SCALAR_NULL :
            null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return in_array($type, self::SCALAR_TYPES, true)
            ? self::TYPE_SCALAR_NULL
            : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? (string) $value
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
