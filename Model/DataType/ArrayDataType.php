<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;

use Hyva\Admin\Api\DataTypeInterface;
use function array_map as map;
use function array_merge as merge;
use function array_slice as slice;

class ArrayDataType implements DataTypeInterface
{
    public const TYPE_ARRAY = 'array';
    public const LIMIT = 5;

    /**
     * @var DataTypeToStringConverterLocatorInterface
     */
    private $toStringConverterLocator;

    /**
     * @var DataTypeGuesserInterface
     */
    private $dataTypeGuesser;

    public function __construct(
        DataTypeToStringConverterLocatorInterface $toStringConverterLocator,
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
            ? (empty($value) ? '[ ]' : $this->implode($value, 1))
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = 1): ?string
    {
        return $this->valueToTypeCode($value)
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
            : '[' . ($this->mayRecurse($maxRecursionDepth)
                ? implode(', ', $this->recur($value, $maxRecursionDepth))
                : '...') . ']';
    }

    private function recur(array $value, int $maxRecursionDepth): array
    {
        $overLimit   = count($value) > self::LIMIT;
        $itemsToShow = $overLimit ? slice($value, 0, self::LIMIT) : $value;

        $strings = map(function ($value) use ($maxRecursionDepth): string {
            $converter = $this->toStringConverterLocator->forTypeCode($this->dataTypeGuesser->valueToTypeCode($value));
            return $converter->toHtmlRecursive($value, $maxRecursionDepth - 1);
        }, $itemsToShow);

        return merge($strings, ($overLimit ? ['...'] : []));
    }
}
