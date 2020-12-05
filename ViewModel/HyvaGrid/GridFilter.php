<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Api\GridFilterTypeInterface;
use Hyva\Admin\Model\GridFilter\GridFilterTypeLocator;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;

use function array_map as map;

class GridFilter implements GridFilterInterface
{
    private string $gridName;

    private string $filterFormId;

    private ColumnDefinitionInterface $columnDefinition;

    private FilterOptionInterfaceFactory $filterOptionFactory;

    private RequestInterface $request;

    private GridFilterTypeLocator $gridFilterTypeLocator;

    private ?string $enabled;

    private ?array $options;

    private ?string $filterType;

    private ?string $template;

    public function __construct(
        string $gridName,
        string $filterFormId,
        ColumnDefinitionInterface $columnDefinition,
        GridFilterTypeLocator $gridFilterTypeLocator,
        FilterOptionInterfaceFactory $filterOptionFactory,
        RequestInterface $request,
        ?string $enabled = null,
        ?string $filterType = null,
        ?string $template = null,
        ?array $options = null
    ) {
        $this->gridName              = $gridName;
        $this->columnDefinition      = $columnDefinition;
        $this->filterOptionFactory   = $filterOptionFactory;
        $this->request               = $request;
        $this->enabled               = $enabled;
        $this->filterType            = $filterType;
        $this->options               = $options;
        $this->filterFormId          = $filterFormId;
        $this->gridFilterTypeLocator = $gridFilterTypeLocator;
        $this->template              = $template;
    }

    public function getHtml(): string
    {
        return $this->isDisabled()
            ? ''
            : $this->renderFilter();
    }

    private function renderFilter(): string
    {
        $renderer = $this->getFilterType()->getRenderer($this->getColumnDefinition());
        if ($this->template) {
            $renderer->setTemplate($this->template);
        }
        $renderer->assign('filter', $this);

        return $renderer->toHtml();
    }

    private function getFilterType(): GridFilterTypeInterface
    {
        return $this->filterType
            ? $this->gridFilterTypeLocator->get($this->filterType)
            : $this->gridFilterTypeLocator->findFilterForColumn($this, $this->getColumnDefinition());
    }

    public function getColumnDefinition(): ColumnDefinitionInterface
    {
        return $this->columnDefinition;
    }

    public function isDisabled(): bool
    {
        return $this->enabled === 'false';
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
            'label'  => (string) __($optionConfig['label']),
            'values' => $optionConfig['values'] ?? ($optionConfig['value'] ? [$optionConfig['value']] : []),
        ];
        return $this->filterOptionFactory->create($arguments);
    }

    public function getInputName(string $aspect = null): string
    {
        $key = $this->getColumnDefinition()->getKey();
        return isset($aspect)
            ? sprintf('%s[_filter][%s][%s]', $this->gridName, $key, $aspect)
            : sprintf('%s[_filter][%s]', $this->gridName, $key);
    }

    public function getValue(string $aspect = null)
    {
        $gridQueryParams = $this->request->getParam($this->gridName);
        $value           = $gridQueryParams['_filter'][$this->getColumnDefinition()->getKey()] ?? null;
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

    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        if (!$this->isDisabled()) {
            $this->getFilterType()->apply($searchCriteriaBuilder, $this, $this->getValue());
        }
    }
}
