<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class LongTextDataType implements DataTypeInterface
{
    const TYPE_LONG_TEXT = 'long_text';
    const LONG_TEXT_MIN_LENGTH = 200;
    const MAX_LENGTH = 30;

    const LONG_TEXT_TYPES = [
        self::TYPE_LONG_TEXT,
        'string',
        'text',
    ];

    public function valueToTypeCode($value): ?string
    {
        return is_string($value)
            ? self::TYPE_LONG_TEXT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return in_array($type, self::LONG_TEXT_TYPES, true) ? self::TYPE_LONG_TEXT : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatLongText($value)
            : null;
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
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
