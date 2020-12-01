<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\DataType\BooleanDataType;
use Hyva\Admin\Model\DataType\DateTimeDataTypeConverter;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class GridFilter implements GridFilterInterface
{
    private ColumnDefinitionInterface $columnDefinition;

    private LayoutInterface $layout;

    private ?string $input;

    private ?string $enabled;

    private ?string $template;

    private ?array $options;

    /**
     * @var string[]
     */
    private array $inputTypeTemplateMap;

    public function __construct(
        ColumnDefinitionInterface $columnDefinition,
        LayoutInterface $layout,
        array $inputTypeTemplateMap,
        ?string $input = null,
        ?string $enabled = null,
        ?string $template = null,
        ?array $options = null
    ) {
        $this->columnDefinition     = $columnDefinition;
        $this->layout               = $layout;
        $this->inputTypeTemplateMap = $inputTypeTemplateMap;
        $this->input                = $input;
        $this->enabled              = $enabled;
        $this->template             = $template;
        $this->options              = $options;
    }

    public function getHtml(): string
    {
        $renderer = $this->createRenderer();

        if (! $template = $this->getTemplate()) {
            $msg = sprintf('No template is set for the grid filter input type "%s".', $this->getInputType());
            throw new \OutOfBoundsException($msg);
        }
        $renderer->setTemplate($template);
        $renderer->assign('filter', $this);

        return $renderer->toHtml();
    }

    public function getColumnDefinition(): ColumnDefinitionInterface
    {
        return $this->columnDefinition;
    }

    public function isEnabled(): bool
    {
        return $this->enabled === 'true';
    }

    public function getInputType(): string
    {
        return $this->input ?? $this->guessInputType();
    }

    private function guessInputType(): string
    {
        if ($this->getColumnDefinition()->getOptionArray()) {
            return 'select';
        }
        if ($this->getColumnDefinition()->getType() === BooleanDataType::TYPE_BOOL) {
            return 'bool';
        }
        if ($this->getColumnDefinition()->getType() === DateTimeDataTypeConverter::TYPE_DATETIME) {
            return 'date-range';
        }
        return 'text';
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    private function getTemplate(): ?string
    {
        return $this->template ?? $this->inputTypeTemplateMap[$this->getInputType()] ?? null;
    }

    /**
     * @return BlockInterface|Template
     */
    private function createRenderer(): Template
    {
        return $this->layout->createBlock(Template::class);
    }

    public function getInputName(string $aspect = null): string
    {
        // todo: this needs to be qualified by grid name and, if provided, aspect [gridName][key][aspect]
        return $this->getColumnDefinition()->getKey();
    }

    public function getValue(): ?string
    {
        return null;
    }
}
