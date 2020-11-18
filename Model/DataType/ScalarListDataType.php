<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

use function array_reduce as reduce;
use function array_slice as slice;

class ScalarListDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_STRING_LIST = 'scalar_list';
    const LIMIT = 10;

    public function typeOf($value): ?string
    {
        return $this->isListOfScalars($value)
            ? self::TYPE_STRING_LIST
            : null;
    }

    public function toString($value): ?string
    {
        return $this->isListOfScalars($value)
            ? $this->implode($value)
            : null;
    }

    private function implode(array $value): string
    {
        $overLimit   = count($value) > self::LIMIT;
        $itemsToShow = $overLimit ? slice($value, 0, self::LIMIT) : $value;
        $indicator   = $overLimit ? ',...' : '';

        return '["' . implode('", "', $itemsToShow) . '"' . $indicator . ']';
    }

    private function isListOfScalars($value): bool
    {
        return is_array($value) && $this->areFirstNItemsScalar($value, 10);
    }

    private function areFirstNItemsScalar(array $value, int $numberOfItemsToCheck): bool
    {
        return reduce(
            slice($value, 0, $numberOfItemsToCheck),
            function (bool $acc, $value): bool {
                return $acc && is_scalar($value);
            },
            true
        );
    }
}
