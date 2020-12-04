<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\Api\SearchCriteriaBuilder;

interface GridFilterInterface
{
    public function getColumnDefinition(): ColumnDefinitionInterface;

    public function getHtml(): string;

    public function isDisabled(): bool;

    /**
     * @return FilterOptionInterface[]|null
     */
    public function getOptions(): ?array;

    public function getInputName(string $aspect = null): string;

    /**
     * @param string|null $aspect
     * @return mixed
     */
    public function getValue(string $aspect = null);

    public function getFormId(): string;

    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder): void;
}
