# grid > navigation > filters > filter

A `filter` element enables (or disables) filtering for a column.

Unless a filter is configured explicitly, a column is not filterable.

```html
<filter column="sku"/>
```

How a filter is rendered and is applied is determined by the column definition:

* Column type `bool` → Filter type `boolean`
* Column type `datetime` → Filter type `date-range`
* Column type `int` → Filter type `value-range`
* Column with options → Filter type `select`
* All other → Filter type `text`

There is one exception: if a filter is has options or a source attribute, it will always be rendered as a select filter, regardless of the columns type.

It is also possible to create custom filter types (see below).

The `filter` element as one required and four optional attributes.

### column (required)

This attribute refers to the column that should be filterable. The attribute value is the column name.

### template

When the default filter templates are not sufficient, a custom filter template can be specified with the `template` attribute in the common Magento `Vendor_Module:template-file.phtml` notation.

```html
<filter column="images" template="My_Module::grid/filters/image-filter.phtml"/>
```

The filter instance is injected in the template automatically with the variable `$filter`.

A PHPDoc type hint can be added with the following code to supply IDE auto-completion:

```php
/** @var Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface $filter */
```

The included templates can be used as a reference and can be found in the Hyva_Admin module directory `view/adminhtml/templates/filter`.

### enabled

The enabled attribute can be used to disable a filter that was declared in another grid XML configuration.

```html
<filter column="sku" enabled="false"/>
```

### filterType

The filterType attribute is used to specify a custom filter type class.

```html
<filter column="category_links"
        filterType="My\Module\Admihtml\GridFilter\CategoryLinksGridFilter"/>
```

Filter types are responsible for supplying the filter renderer (a template block instance) and for applying filter values to a `SearchCriteria` instance.

All filter types implement the interface `Hyva\Admin\Api\GridFilterTypeInterface`.

More information can be found in the Hyva_Admin PHP Classes and Interfaces reference.

### source

The `source` attribute is used to specify a source model class or interface to get the select options for the filter.

When a `source` attribute is present, the column filter will always be rendered as a select filter, regardless of the columns type.

The only requirement for the source class is it has a `toOptionArray` method (all Magento source models have that method). This method has to return the options in the usual Magento format `[['value' => $value, 'label' => $label], …]`.

Currently a source model overrides filter options specified in the grid XML, but this behavior is not guaranteed to be stable.

```html
<filter column="store_id" source="Magento\Config\Model\Config\Source\Store"/>
```

