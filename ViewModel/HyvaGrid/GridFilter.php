<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\DataType\BooleanDataType;
use Hyva\Admin\Model\DataType\DateTimeDataTypeConverter;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

use function array_combine as zip;
use function array_filter as filter;
use function array_map as map;
use function array_values as values;

class GridFilter implements GridFilterInterface
{
    private string $gridName;

    private string $filterFormId;

    private ColumnDefinitionInterface $columnDefinition;

    private LayoutInterface $layout;

    private FilterOptionInterfaceFactory $filterOptionFactory;

    /**
     * @var string[]
     */
    private array $inputTypeTemplateMap;

    private RequestInterface $request;

    private ?string $input;

    private ?string $enabled;

    private ?string $template;

    private ?array $options;

    public function __construct(
        string $gridName,
        string $filterFormId,
        ColumnDefinitionInterface $columnDefinition,
        LayoutInterface $layout,
        FilterOptionInterfaceFactory $filterOptionFactory,
        RequestInterface $request,
        array $inputTypeTemplateMap,
        ?string $input = null,
        ?string $enabled = null,
        ?string $template = null,
        ?array $options = null
    ) {
        $this->gridName             = $gridName;
        $this->columnDefinition     = $columnDefinition;
        $this->layout               = $layout;
        $this->filterOptionFactory  = $filterOptionFactory;
        $this->request              = $request;
        $this->inputTypeTemplateMap = $inputTypeTemplateMap;
        $this->input                = $input;
        $this->enabled              = $enabled;
        $this->template             = $template;
        $this->options              = $options;
        $this->filterFormId         = $filterFormId;
    }

    public function getHtml(): string
    {
        return $this->isDisabled()
            ? ''
            : $this->renderFilter();
    }

    private function renderFilter(): string
    {
        $renderer = $this->createRenderer();

        if (!($template = $this->getTemplate())) {
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

    public function isDisabled(): bool
    {
        return $this->enabled === 'false';
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
        $options = $this->options ?? $this->getColumnDefinition()->getOptionArray() ?? null;
        return $options
            ? map([$this, 'buildFilterOption'], $options)
            : null;
    }

    private function buildFilterOption(array $optionConfig): FilterOptionInterface
    {
        $arguments = [
            'label'  => $optionConfig['label'],
            'values' => $optionConfig['values'] ?? ($optionConfig['value'] ? [$optionConfig['value']] : []),
        ];
        return $this->filterOptionFactory->create($arguments);
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
        $key = $this->getColumnDefinition()->getKey();
        return isset($aspect)
            ? sprintf('%s[filter][%s][%s]', $this->gridName, $key, $aspect)
            : sprintf('%s[filter][%s]', $this->gridName, $key);
    }

    public function getValue(string $aspect = null)
    {
        $gridQueryParams = $this->request->getParam($this->gridName);
        $value           = $gridQueryParams['filter'][$this->getColumnDefinition()->getKey()] ?? null;
        return isset($aspect) && $value
            ? $this->extractValueAspect($aspect, $value)
            : $value;
    }

    private function extractValueAspect(string $aspect, $value)
    {
        if (!is_array($value)) {
            $msg = sprintf(
                'Expected query parameter [%s][%s] to be an array, got "%s"',
                $this->getColumnDefinition()->getKey(),
                $aspect,
                gettype($value)
            );
            throw new \RuntimeException($msg);
        }
        return $value[$aspect] ?? null;
    }

    public function getFormId(): string
    {
        return $this->filterFormId;
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }

    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        if ($this->isDisabled()) {
            return;
        }
        $key = $this->getColumnDefinition()->getKey();
        switch ($this->getInputType()) {
            case 'text':
                if ($this->isValue($value = $this->getValue())) {
                    $searchCriteriaBuilder->addFilter($key, '%' . $value . '%', 'like');
                }
                return;
            case 'bool':
                if ($this->isValue($value = $this->getValue())) {
                    $searchCriteriaBuilder->addFilter($key, (int) $value, 'eq');
                }
                return;
            case 'select':
                if ($selectedOption = $this->getSelectedOption()) {
                    $searchCriteriaBuilder->addFilter($key, $selectedOption->getValues(), 'finset');
                }
                return;
            case 'date-range':
                if ($this->isValue($from = $this->getValue('from'))) {
                    $searchCriteriaBuilder->addFilter($key, $from, 'from');
                }
                if ($this->isValue($to = $this->getValue('to'))) {
                    $searchCriteriaBuilder->addFilter($key, $to, 'to');
                }
                return;
            case 'value-range':
                if ($this->isValue($from = $this->getValue('from'))) {
                    $searchCriteriaBuilder->addFilter($key, $from, 'gteq');
                }
                if ($this->isValue($to = $this->getValue('to'))) {
                    $searchCriteriaBuilder->addFilter($key, $to, 'lteq');
                }
                return;
        }
    }

    private function getSelectedOption(): ?FilterOptionInterface
    {
        $value = $this->getValue();
        return values(filter($this->getOptions(), function (FilterOptionInterface $option) use ($value): bool {
            return $option->getValueId() === $value;
        }))[0] ?? null;
    }
}
