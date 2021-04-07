<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Api\HyvaGridFilterTypeInterface;
use Hyva\Admin\Model\GridFilter\FilterSourceModelFactory;
use Hyva\Admin\Model\GridFilter\GridFilterTypeLocator;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;

use function array_map as map;

class GridFilter implements GridFilterInterface
{
    /**
     * @var string
     */
    private $gridName;

    /**
     * @var string
     */
    private $filterFormId;

    /**
     * @var ColumnDefinitionInterface
     */
    private $columnDefinition;

    /**
     * @var FilterOptionInterfaceFactory
     */
    private $filterOptionFactory;

    /**
     * @var FilterSourceModelFactory
     */
    private $filterSourceModelFactory;

    /**
     * @var GridFilterTypeLocator
     */
    private $gridFilterTypeLocator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string|null
     */
    private $enabled;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @var string|null
     */
    private $filterType;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var string|null
     */
    private $source;

    public function __construct(
        string $gridName,
        string $filterFormId,
        ColumnDefinitionInterface $columnDefinition,
        GridFilterTypeLocator $gridFilterTypeLocator,
        FilterOptionInterfaceFactory $filterOptionFactory,
        FilterSourceModelFactory $filterSourceModelFactory,
        RequestInterface $request,
        ?string $enabled = null,
        ?string $filterType = null,
        ?string $template = null,
        ?array $options = null,
        ?string $source = null
    ) {
        $this->gridName                 = $gridName;
        $this->columnDefinition         = $columnDefinition;
        $this->filterOptionFactory      = $filterOptionFactory;
        $this->request                  = $request;
        $this->enabled                  = $enabled;
        $this->filterType               = $filterType;
        $this->options                  = $options;
        $this->filterFormId             = $filterFormId;
        $this->gridFilterTypeLocator    = $gridFilterTypeLocator;
        $this->template                 = $template;
        $this->source                   = $source;
        $this->filterSourceModelFactory = $filterSourceModelFactory;
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

    private function getFilterType(): HyvaGridFilterTypeInterface
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
        $options = $this->options ?? $this->getSourceOptions() ?? $this->getColumnDefinitonOptions() ?? null;
        return $options
            ? map([$this, 'buildFilterOption'], $options)
            : null;
    }

    private function getColumnDefinitonOptions(): ?array
    {
        return $this->getColumnDefinition()->getOptionArray();
    }

    private function getSourceOptions(): ?array
    {
        return $this->source
            ? $this->filterSourceModelFactory->create($this->source)->toOptionArray()
            : null;
    }

    private function buildFilterOption(array $optionConfig): FilterOptionInterface
    {
        $arguments = [
            'label'  => (string) __($optionConfig['label']),
            'values' => $optionConfig['values'] ?? (isset($optionConfig['value']) ? [$optionConfig['value']] : []),
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
