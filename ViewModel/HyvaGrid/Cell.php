<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\DataType\DataTypeToStringConverter;
use Magento\Framework\View\LayoutInterface;

class Cell implements CellInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ColumnDefinitionInterface $columnDefinition;

    private DataTypeToStringConverter $dataTypeToStringConverter;

    private LayoutInterface $layout;

    public function __construct(
        $value,
        ColumnDefinitionInterface $columnDefinition,
        DataTypeToStringConverter $dataTypeToStringConverter,
        LayoutInterface $layout
    ) {
        $this->value                     = $value;
        $this->columnDefinition          = $columnDefinition;
        $this->dataTypeToStringConverter = $dataTypeToStringConverter;
        $this->layout                    = $layout;
    }

    public function getHtml(): string
    {
        $renderer = $this->columnDefinition->getRenderer();
        return $renderer
            ? $this->layout->createBlock($renderer)->setData('cell', $this)->toHtml()
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
        $type = $this->columnDefinition->getType();
        $str  = $this->dataTypeToStringConverter->toString($type, $value);
        try {
            return $str ?? (string) $value;// last ditch effort to convert the value to a string
        } catch (\Throwable $exception) {
            $key = $this->columnDefinition->getKey();
            $msg = sprintf('Unable to cast a value of column "%s" with type "%s" to a string', $key, $type);
            throw new \RuntimeException($msg);
        }
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
}
