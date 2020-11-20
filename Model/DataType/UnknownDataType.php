<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

class UnknownDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_UNKNOWN = 'unknown';

    public function typeOf($value): ?string
    {
        return self::TYPE_UNKNOWN;
    }

    public function toString($value): ?string
    {
        try {
            return (string) $value;// last ditch effort to convert the value to a string
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf(
                'Unable to cast a value of unknown type "%s" (%s) to a string',
                gettype($value),
                is_object($value) ? get_class($value) : (is_array($value) ? 'array' : 'unknown')
            ));
        }
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
