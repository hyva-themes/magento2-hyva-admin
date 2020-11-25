<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

use function array_map as map;
use function array_merge as merge;
use function array_slice as slice;

class ArrayDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_ARRAY = 'array';
    const LIMIT = 10;

    private DataTypeToStringConverterLocator $toStringConverterLocator;

    private DataTypeGuesserInterface $dataTypeGuesser;

    public function __construct(
        DataTypeToStringConverterLocator $toStringConverterLocator,
        DataTypeGuesserInterface $dataTypeGuesser
    ) {
        $this->toStringConverterLocator = $toStringConverterLocator;
        $this->dataTypeGuesser          = $dataTypeGuesser;
    }

    public function valueToTypeCode($value): ?string
    {
        return is_array($value) ? self::TYPE_ARRAY : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_ARRAY ? $type : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? (empty($value) ? '[ ]' : sprintf('[...(%d)...]', count($value)))
            : null;
    }

    public function toStringRecursive($value, $maxRecursionDepth = 1): ?string
    {
        return $this->valueToTypeCode($value) && $this->mayRecurse($maxRecursionDepth)
            ? $this->implode($value, $maxRecursionDepth)
            : $this->toString($value);
    }

    private function mayRecurse(int $depth): bool
    {
        return $depth <= self::UNLIMITED_RECURSION || $depth > 0;
    }

    private function implode(array $value, $maxRecursionDepth): string
    {
        return empty($value)
            ? '[ ]'
            : '[' . implode(', ', $this->recur($value, $maxRecursionDepth)) . ']';
    }

    private function recur(array $value, int $maxRecursionDepth): array
    {
        $overLimit   = count($value) > self::LIMIT;
        $itemsToShow = $overLimit ? slice($value, 0, self::LIMIT) : $value;

        $strings = map(function ($value) use ($maxRecursionDepth): string {
            $converter = $this->toStringConverterLocator->forTypeCode($this->dataTypeGuesser->valueToTypeCode($value));
            return $converter->toStringRecursive($value, $maxRecursionDepth - 1);
        }, $itemsToShow);

        return merge($strings, ($overLimit ? ['...'] : []));
    }
}
