<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface CellInterface
{
    public function getHtml(): string;

    // probably needs methods to return:
    // - column name
    // - unescaped value
    // - column type (?)
    // - a boolean method if the value could be successfully serialized to a string
    // - maybe css classes...
}
