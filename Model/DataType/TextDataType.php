<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

/**
 * This data type to string converter returns the column value as a string, truncated after 30 characters.
 */
class TextDataType implements DataTypeInterface
{
    public const TYPE_TRUNCATED_TEXT = 'truncated_text';
    public const MAX_LENGTH = 30;

    private const TEXT_TYPES = [
        self::TYPE_TRUNCATED_TEXT,
        'text',
        'string',
        'varchar'
    ];

    public function valueToTypeCode($value): ?string
    {
        return is_string($value)
            ? self::TYPE_TRUNCATED_TEXT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return in_array($type, self::TEXT_TYPES, true) ? self::TYPE_TRUNCATED_TEXT : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatLongText($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function formatLongText(string $value): string
    {
        return mb_strlen($value) > (self::MAX_LENGTH - 3)
            ? mb_substr($value, 0, self::MAX_LENGTH) . '...'
            : $value;
    }
}
