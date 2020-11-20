<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\DataType\DataTypeToStringConverterLocator;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class Cell implements CellInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ColumnDefinitionInterface $columnDefinition;

    private DataTypeToStringConverterLocator $dataTypeToStringConverterLocator;

    private LayoutInterface $layout;

    public function __construct(
        $value,
        ColumnDefinitionInterface $columnDefinition,
        DataTypeToStringConverterLocator $dataTypeToStringConverter,
        LayoutInterface $layout
    ) {
        $this->value                            = $value;
        $this->columnDefinition                 = $columnDefinition;
        $this->dataTypeToStringConverterLocator = $dataTypeToStringConverter;
        $this->layout                           = $layout;
    }

    public function getHtml(): string
    {
        $renderer = $this->getRenderer();
        return $renderer
            ? $renderer->setData('cell', $this)->toHtml()
            : $this->getTextValue();
    }

    public function getColumnDefinition(): ColumnDefinitionInterface
    {
        return $this->columnDefinition;
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function getTextValue(): ?string
    {
        $options = $this->columnDefinition->getOptionArray();
        return $options
            ? $this->getOptionText($this->getRawValue())
            : $this->toString($this->getRawValue());
    }

    private function toString($value): string
    {
        $converter = $this->dataTypeToStringConverterLocator->forType($this->columnDefinition->getType());
        $string    = $converter->toStringRecursive($value, 1 /* recursion depth */);
        return $string ?? '#type';
    }

    private function getOptionText(array $options): ?string
    {
        foreach ($options as $option) {
            if ($option['value'] === $this->getRawValue()) {
                return $option['label'];
            }
        }
        return null;
    }

    private function getRenderer(): ?AbstractBlock
    {
        if ($template = $this->columnDefinition->getTemplate()) {
            $renderer = $this->createTemplateBlock($template);
        } elseif ($rendererBlockName = $this->columnDefinition->getRendererBlockName()) {
            $renderer = $this->layout->getBlock($rendererBlockName);
        }
        return $renderer ?? null;
    }

    private function createTemplateBlock(string $template): AbstractBlock
    {
        /** @var Template $renderer */
        $renderer = $this->layout->createBlock(Template::class);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
