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
        $html     = $renderer
            ? $renderer->setData('cell', $this)->toHtml()
            : $this->getTextValue();
        return $html;
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
        if (is_null($this->getRawValue())) {
            return '';
        }
        $options   = $this->columnDefinition->getOptionArray();
        $textValue = $options && !is_array($this->getRawValue())
            ? $this->getOptionText($options, $this->getRawValue()) ?? ((string) $this->getRawValue())
            : $this->toString($this->getRawValue());
        return $textValue;
    }

    private function toString($value): string
    {
        $columnType = $this->columnDefinition->getType();
        $converter  = $this->dataTypeToStringConverterLocator->forTypeCode($columnType);
        $string     = $converter
            ? $converter->toStringRecursive($value, 1 /* recursion depth */) ?? $this->missmatch($columnType, $value)
            : '#unknownType(' . $columnType . ')';
        return $string;
    }

    private function missmatch(?string $columnType, $value): string
    {
        return sprintf('Column Type "%s" and value of type "%s" do not match', $columnType, gettype($value));
    }

    private function getOptionText(array $options, $value): ?string
    {
        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return (string) $option['label'];
            }
        }
        return null;
    }

    private function getRenderer(): ?AbstractBlock
    {
        if ($template = $this->columnDefinition->getTemplate()) {
            $renderer = $this->createTemplateBlock($template);
        } elseif ($rendererBlockName = $this->columnDefinition->getRendererBlockName()) {
            $renderer = $this->getBlockFromLayout($rendererBlockName);
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

    private function getBlockFromLayout(string $blockName): ?AbstractBlock
    {
        $renderer = $this->layout->getBlock($blockName);
        if (!$renderer instanceof AbstractBlock) {
            $key = $this->getColumnDefinition()->getKey();
            $msg = sprintf('The renderer block "%s" for column "%s" can\'t be found.', $blockName, $key);
            throw new \LogicException($msg);
        }
        return $renderer;
    }
}
