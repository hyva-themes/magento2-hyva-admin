<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface as TypeGuesser;

use function array_reduce as reduce;

class CompositeDataTypeGuesser implements TypeGuesser
{
    private array $dataTypeGuessers;

    private DataTypeGuesserPool $dataTypeGuesserPool;

    public function __construct(array $dataTypeGuessers, DataTypeGuesserPool $dataTypeGuesserPool)
    {
        $this->dataTypeGuessers    = $dataTypeGuessers;
        $this->dataTypeGuesserPool = $dataTypeGuesserPool;
    }

    public function valueToTypeCode($value): ?string
    {
        return reduce($this->dataTypeGuessers, function (?string $type, string $class) use ($value): ?string {
            return $type ?? $this->dataTypeGuesserPool->get($class)->valueToTypeCode($value);
        }, null);
    }

    public function typeToTypeCode(string $type): ?string
    {
        return reduce($this->dataTypeGuessers, function (?string $typeCode, string $class) use ($type): ?string {
            return $typeCode ?? $this->dataTypeGuesserPool->get($class)->typeToTypeCode($type);
        }, null);

    }
}
