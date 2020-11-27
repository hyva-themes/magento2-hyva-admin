<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface CellInterface
{
    public function getHtml(): string;

    public function getColumnDefinition(): ColumnDefinitionInterface;

    /**
     * @return mixed
     */
    public function getRawValue();

    public function getTextValue(): string;

    public function isVisible(): bool;

    /**
     * Used by custom cell renderers to access the values of other cells in the same row.
     *
     * For example:
     * $cell->getRow()->getCell('other_col')->getTextValue()
     *
     * Please note: it's not possible for a cell to access itself from the given row.
     * $key = $cell->getColumnDefinition()->getKey();
     * $cell->getRow()->getCell($key)->getTextValue(); // <-- call getTextValue on null error
     */
    public function getRow(): ?RowInterface;
}
