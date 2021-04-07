<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use PHPUnit\Framework\Constraint\Constraint;

use function array_filter as filter;
use function array_values as values;

class ConstraintContainsColumn extends Constraint
{
    /** @var ColumnDefinitionInterface[] */
    private $actualColumns;

    public function __construct(array $actualColumns)
    {
        $this->actualColumns = $actualColumns;
    }

    /**
     * @param ColumnDefinitionInterface $other
     * @return bool
     */
    protected function matches($other): bool
    {
        foreach ($this->actualColumns as $actual) {
            if ($actual->toArray() === $other->toArray()) {
                return true;
            }
        }
        return false;
    }

    final protected function hasColumnWithKey(string $key): bool
    {
        foreach ($this->actualColumns as $actual) {
            if ($actual->getKey() === $key) {
                return true;
            }
        }
        return false;
    }

    private function findActualColumnByKey(string $key): ?ColumnDefinitionInterface
    {
        return values(filter($this->actualColumns, function (ColumnDefinitionInterface $columnDefinition) use ($key): bool {
                return $columnDefinition->getKey() === $key;
            }))[0] ?? null;
    }

    public function toString(): string
    {
        return 'is contained in column list';
    }

    protected function failureDescription($other): string
    {
        $actual = $this->findActualColumnByKey($other->getKey());
        return $actual
            ? sprintf(
                "column '%s' properties match expected\n%s\nActual %s", $other->getKey(),
                $this->exporter()->export($other->toArray()),
                $this->exporter()->export($actual->toArray())
            )
            : sprintf('column "%s" exists', $other->getKey());
    }
}
