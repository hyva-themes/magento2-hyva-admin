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

    // probably needs methods to return:
    // - a boolean method if the value could be successfully serialized to a string
    // - maybe css classes...
}
