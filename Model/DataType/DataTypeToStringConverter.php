<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

class DataTypeToStringConverter
{
    /**
     * @var DataTypeValueToStringConverterInterface[]
     */
    private array $valueToStringConverters;

    public function __construct(array $valueToStringConverterClasses)
    {
        $this->valueToStringConverters = $valueToStringConverterClasses;
    }

    private function forType(string $type): ?DataTypeValueToStringConverterInterface
    {
        return $this->valueToStringConverters[$type] ?? null;
    }

    public function toString(string $type, $value): ?string
    {
        $converter = $this->forType($type);
        return $converter ? $converter->toString($value) : null;
    }
}
