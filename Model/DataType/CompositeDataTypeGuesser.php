<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface as TypeGuesser;

use function array_reduce as reduce;

class CompositeDataTypeGuesser implements TypeGuesser
{
    private array $dataTypeGuessers;

    private DataTypeGuesserFactory $dataTypeGuesserFactory;

    public function __construct(array $dataTypeGuessers, DataTypeGuesserFactory $dataTypeGuesserFactory)
    {
        $this->dataTypeGuessers = $dataTypeGuessers;
        $this->dataTypeGuesserFactory = $dataTypeGuesserFactory;
    }

    public function typeOf($value): ?string
    {
        return reduce($this->dataTypeGuessers, function (?string $type, string $class) use ($value): ?string {
            return $type ?? $this->dataTypeGuesserFactory->get($class)->typeOf($value);
        }, null);
    }
}
