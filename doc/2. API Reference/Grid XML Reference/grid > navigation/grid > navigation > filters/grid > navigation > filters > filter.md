# grid > navigation > filters > filter

A `filter` element enables (or disables) filtering for a column.

Unless a filter is configured specifically, a column is not filterable.


```markup
<filter column="sku"/>
```


How a filter is rendered and is applied is determined by the column definition:

* Column type `bool` → Filter type `boolean`
* Column type `datetime` → Filter type `date-range`
* Column type `int` → Filter type `value-range`
* Column with options → Filter type `select`
* All other → Filter type `text`

It is also possible to create custom filter types (see below).


The `filter` element as one required and three optional attributes.

### column (required)

This attribute refers to the column that should be filterable. The attribute value is the column name.

### template

When the default filter templates are not sufficient, a custom filter template can be specified with the `template` attribute in the common Magento `Vendor_Module:template-file.phtml` notation.

```markup
<filter column="images" template="My_Module::grid/filters/image-filter.phtml"/>
```


The filter instance is injected in the template automatically with the variable `$filter`.

A PHPDoc type hint can be added with the following code to supply IDE auto-completion:

```php
/** @var \Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface $filter */
```


The included templates can be used as a reference and can be found in the Hyva_Admin module directory `view/adminhtml/templates/filter`.

### enabled

The enabled attribute can be used to disable a filter that was declared in another grid XML configuration.

```markup
<filter column="sku" enabled="false"/>
```

### filterType

The filterType attribute is used to specify a custom filter type class.

```markup
<filter column="category_links"
        filterType="My\Module\Admihtml\Grid\Filter\CategoryLinksGridFilter"/>
```


Filter types are responsible for supplying the filter renderer (a template block instance) and for applying filter values to a `SearchCriteria` instance.

All filter types implement the interface `\Hyva\Admin\Api\GridFilterTypeInterface`.

More information can be found in the Hyva_Admin PHP Classes and Interfaces reference.