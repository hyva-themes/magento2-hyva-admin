<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Exception\UnableToCastToStringException;
use Hyva\Admin\Model\DataType\DataTypeToStringConverterLocatorInterface;
use Magento\Framework\Escaper;
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

    private DataTypeToStringConverterLocatorInterface $dataTypeToStringConverterLocator;

    private ?RowInterface $row;

    private LayoutInterface $layout;

    private Escaper $escaper;

    public function __construct(
        $value,
        ColumnDefinitionInterface $columnDefinition,
        DataTypeToStringConverterLocatorInterface $dataTypeToStringConverter,
        LayoutInterface $layout,
        Escaper $escaper,
        RowInterface $row = null
    ) {
        $this->value                            = $value;
        $this->columnDefinition                 = $columnDefinition;
        $this->dataTypeToStringConverterLocator = $dataTypeToStringConverter;
        $this->row                              = $row;
        $this->layout                           = $layout;
        $this->escaper                          = $escaper;
    }

    public function getHtml(): string
    {
        $renderer = $this->getRenderer();
        return $renderer
            ? $renderer->setData('cell', $this)->toHtml()
            : $this->renderAsHtml();
    }

    private function renderAsHtml(): string
    {
        try {
            return $this->getColumnDefinition()->getRenderAsUnsecureHtml()
                ? $this->convertToString($this->getRawValue(), true)
                : $this->escaper->escapeHtml($this->getTextValue());

        } catch (UnableToCastToStringException $exception) {
            $msg = sprintf('Column "%s": %s', $this->getColumnDefinition()->getKey(), $exception->getMessage());
            throw new \RuntimeException($msg, 0, $exception);
        }
    }

    public function getColumnDefinition(): ColumnDefinitionInterface
    {
        return $this->columnDefinition;
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function getTextValue(): string
    {
        if (is_null($this->getRawValue())) {
            return '';
        }
        $options = $this->columnDefinition->getOptionArray();
        return $options && !is_array($this->getRawValue())
            ? $this->getOptionText($options, $this->getRawValue())
            : $this->toString($this->getRawValue());
    }

    private function toString($value): string
    {
        return $this->convertToString($value, false);
    }

    private function convertToString($value, bool $useRecursion): string
    {
        $columnType = $this->columnDefinition->getType();
        $converter  = $this->dataTypeToStringConverterLocator->forTypeCode($columnType);
        if (!$converter) {
            return '#unknownType(' . $columnType . ')';
        }
        $stringValue = $useRecursion
            ? $converter->toStringRecursive($value, 1 /* recursion depth */)
            : $converter->toString($value);

        return $stringValue ?? $this->mismatch($columnType, $value);
    }

    private function mismatch(?string $columnType, $value): string
    {
        return sprintf('Column Type "%s" and value of type "%s" do not match', $columnType, gettype($value));
    }

    private function getOptionText(array $options, $value): string
    {
        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return (string) $option['label'];
            }
        }
        return $this->toString($this->getRawValue());
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

    public function getRow(): ?RowInterface
    {
        return $this->row;
    }

    public function isVisible(): bool
    {
        return $this->getColumnDefinition()->isVisible();
    }
}
