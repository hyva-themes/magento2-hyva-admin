<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class Cell implements CellInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ColumnDefinitionInterface $columnDefinition;

    public function __construct($value, ColumnDefinitionInterface $columnDefinition)
    {
        $this->value = $value;
        $this->columnDefinition = $columnDefinition;
    }

    public function getHtml(): string
    {
        return (string) $this->value;
    }
}
