<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\View\LayoutInterface;

class Cell implements CellInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ColumnDefinitionInterface $columnDefinition;

    private LayoutInterface $layout;

    public function __construct($value, ColumnDefinitionInterface $columnDefinition, LayoutInterface $layout)
    {
        $this->value            = $value;
        $this->columnDefinition = $columnDefinition;
        $this->layout           = $layout;
    }

    public function getHtml(): string
    {
        $renderer = $this->columnDefinition->getRenderer();
        return $renderer
            ? $this->layout->createBlock($renderer)->setData('value', $this->value)->toHtml()
            : (string) $this->value;
    }
}
