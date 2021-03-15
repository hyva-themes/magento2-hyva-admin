<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class BooleanDataType implements DataTypeInterface
{
    public const TYPE_BOOL = 'bool';

    public function valueToTypeCode($value): ?string
    {
        return is_bool($value)
            ? self::TYPE_BOOL
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_BOOL
            ? self::TYPE_BOOL
            : null;
    }

    public function toString($value): ?string
    {
        return $value ? 'True' : 'False';
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
