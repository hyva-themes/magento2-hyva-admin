<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

/**
 * This data type to string converter returns the column value as an untrucated string.
 * This data type can not be automatically determined, it must be configured as the column type in the grid.
 */
class LongTextDataType implements DataTypeValueToStringConverterInterface
{
    public const TYPE_TEXT = 'long_text';

    public function toString($value): ?string
    {
        return (string) $value;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
