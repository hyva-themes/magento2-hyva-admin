<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use function array_filter as filter;

class Row implements RowInterface
{
    /**
     * @var CellInterface[]
     */
    private $cells;

    /**
     * @param CellInterface[]
     */
    public function __construct(array $cells)
    {
        $this->cells = $cells;
    }

    public function getCells(): array
    {
        return filter($this->cells, function (CellInterface $cell): bool {
            return $cell->isVisible();
        });
    }

    public function getCell(string $key): ?CellInterface
    {
        return $this->cells[$key] ?? null;
    }
}
