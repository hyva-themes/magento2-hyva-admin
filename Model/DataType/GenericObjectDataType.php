<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class GenericObjectDataType implements DataTypeInterface
{
    public const TYPE_GENERIC_OBJECT = 'object';

    public function valueToTypeCode($value): ?string
    {
        return is_object($value)
            ? self::TYPE_GENERIC_OBJECT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_GENERIC_OBJECT || class_exists($type) || interface_exists($type)
            ? self::TYPE_GENERIC_OBJECT
            : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->objectToString($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }


    private function objectToString($value): string
    {
        return method_exists($value, '__toString')
            ? (string) $value
            : sprintf('object(%s)', get_class($value));
    }
}
