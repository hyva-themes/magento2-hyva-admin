# HyvaGridFilterTypeInterface

Filter types are responsible for supplying the filter renderer block and for applying the posted values to a `SearchCriteria` instance.

## Overview

There are a number of filter types in Hyva_Admin out of the box:

* boolean
* date-range
* select
* text
* value-range

Chances are these are all you will ever need. But if there is a column data type that doesn’t match any of these, then you can implement a custom filter type by implementing this interface.

```php
<?php declare(strict_types=1);

namespace HyvaAdminApi;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;

interface HyvaGridFilterTypeInterface
{
    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template;

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void;
}
```

The custom filter type then is used by specifying it in the grid configuration:

```html
<navigation>
    <filters>
        <filter column="some_column" filterType="MyCustomGridFilterType"/>
    </filters>
</navigation>
```

## Interface Methods

### getRenderer(ColumnDefinitionInterface $columnDefinition): Template

This method returns the template block instance that will be used to render the filter above the column.

The `Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface` instance that is currently being rendered will automatically be set on the template block in the variable `$filter` before the blocks `toHtml()` method is called.

Example:

```php
public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
{
    /** @var Template $templateBlock */
    $templateBlock = $this->layout->createBlock(Template::class);
    $templateBlock->setTemplate('Hyva_Admin::grid/filter/date-range.phtml');

    return $templateBlock;
}
```

If a template is specified in the filter configuration XML in addition to a custom filter type, then the template will be set on the the renderer, overwriting any template that might already be set in the getRenderer method.

### apply($searchCriteriaBuilder, $gridFilter, $filterValue): void

The purpose of the `apply` method is to set filter values that where specified in the admin UI on a `SearchCriteriaBuilder` instance.

The full method signature is too long to fit into the title above, so here it is again in all it’s glory:

```php
public function apply(
    SearchCriteriaBuilder $searchCriteriaBuilder,
    GridFilterInterface $gridFilter,
    $filterValue
): void;
```

The first argument is the `$searchCriteriaBuilder` instance that is meant to receive the filter settings in the apply method.

The second argument is the GridFilterInterface instance containing the column definition.

The posted filter value is passed as the third argument, `$filterValue`.

Here is an example taken from the value-range filter type:

```php
public function apply(
    SearchCriteriaBuilder $searchCriteriaBuilder,
    GridFilterInterface $gridFilter,
    $filterValue
): void {
    $key = $gridFilter->getColumnDefinition()->getKey();
    if ($this->isValue($from = $filterValue['from'] ?? '')) {
        $searchCriteriaBuilder->addFilter($key, $from, 'gteq');
    }
    if ($this->isValue($to = $filterValue['to'] ?? '')) {
        $searchCriteriaBuilder->addFilter($key, $to, 'lteq');
    }
}

private function isValue($value): bool
{
    return isset($value) && '' !== $value;
}
```
