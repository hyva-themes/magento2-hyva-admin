# DataTypeValueToStringConverterInterface

This interface is used to convert a value supplied by Magento to a string rendered in a grid cell.

## Overview

Whenever you need to render some data type in grids and you don’t want to use a custom template for that purpose, you can choose to implement this interface instead.

```php
<?php declare(strict_types=1);

namespace HyvaAdminApi;

interface DataTypeValueToStringConverterInterface
{
    const UNLIMITED_RECURSION = -1;

    public function toString($value): ?string;

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string;
}
```

Hyva_Admin uses the column type code to determine the converter to use.

The column type code to converter mapping happens in the adminhtml `di.xml`.

Excerpt of the Hyva_Admin `etc/adminhtml/di.xml` configuration, mapping column types to `DataTypeValueToStringConverter`:

```html
<type name="HyvaAdminModelDataTypeDataTypeFacade">
    <arguments>
        <argument name="dataTypeClassMap" xsi:type="array">
            <item name="datetime" xsi:type="string">Hyva\Admin\Model\Data\TypeDate\TimeDataType</item>
            <item name="price" xsi:type="string">Hyva\Admin\Model\DataType\PriceDataType</item>
            ...
        </argument>
    </arguments>
</type>
```

The array keys are the column data type codes, the values are the converter class names.

A column type code might be automatically determined by a `DataTypeGuesser`, or it could be set explicitly in the grid configuration XML:

```html
<column name="price" type="price"/>
```

When a cell is rendered, the method `toString` is used by default. Only if the column property `renderAsUnsecureHtml="true"` is set for a column, the method `toHtmlRecursive` is used to stringify a value.

## Interface Methods

### toString($value): ?string

The method takes a value and - if it matches the type supported by the converter - returns a string representation. If the type doesn’t match for some reason, the converter should return null. An appropriate exception will be thrown elsewhere.

The returned string should be a plain text representation and not handle HTML related logic.

For example, an image property might be rendered as an image URL, rather than an `<img>` HTML element.

Example:

```php
public function toString($value): ?string
{
    return $this->canProcess($value)
        ? $this->dateTimeFormatter->formatObject(newDateTimeImmutable($value))
        : null;
}
```

### toHtmlRecursive($value, $maxRecursionDepth = -1): ?string

This method is used to convert a supported value to a HTML representation.

If a recursion doesn’t make sense for a given value, it’s okay to just return a string without recursing. The only data type where recursion is currently used is `array`.

In many cases the string and the HTML representation of a value are the same. In such cases `toHtmlRecursive` can directly delegate to `toString`.

Example:

```php
public function toHtmlRecursive(
    $value,
    $maxRecursionDepth = self::UNLIMITED_RECURSION
): ?string {
    return $this->canProcess($value)
        ? sprintf('<img src="%s"/>', $this->getImageUrl($value))
        : null;
}
```

