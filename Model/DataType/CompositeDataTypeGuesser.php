<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;

class CompositeDataTypeGuesser implements DataTypeGuesserInterface
{
    /**
     * @var DataTypeGuesserInterface[]
     */
    private array $dataTypeGuessers;

    public function __construct(array $dataTypeGuessers)
    {
        $this->dataTypeGuessers = $dataTypeGuessers;
    }

    public function typeOf($value): ?string
    {
        foreach ($this->dataTypeGuessers as $dataTypeGuesser) {
            if ($type = $dataTypeGuesser->typeOf($value)) {
                return $type;
            }
        }
        return null;
    }
}
