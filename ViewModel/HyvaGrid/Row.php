<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class Row implements RowInterface
{
    private array $cells;

    /**
     * @param CellInterface[]
     */
    public function __construct(array $cells)
    {
        $this->cells = $cells;
    }

    public function getCells(): array
    {
        return $this->cells;
    }

    public function getCell(string $key): ?CellInterface
    {
        return $this->getCells()[$key] ?? null;
    }
}
