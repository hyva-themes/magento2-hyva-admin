<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface RowInterface
{
    /**
     * @return CellInterface[]
     */
    public function getCells(): array;

    public function getCell(string $key): ?CellInterface;
}
