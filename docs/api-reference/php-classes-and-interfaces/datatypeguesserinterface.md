# DataTypeGuesserInterface

The `DataTypeGuesserInterface` is used to determine the column type given a value or a Magento type. The column type in turn is used to figure out how to render a value in a grid.

## Overview

Most of the time this interface is not implemented directly, but instead the `DataTypeInterface` is implemented instead which combines the `DataTypeGuesserInterface` with the `DataTypeValueToStringConverterInterface`.

```php
<?php declare(strict_types=1);

namespace HyvaAdminApi;

interface DataTypeGuesserInterface
{
    public function valueToTypeCode($value): ?string;

    public function typeToTypeCode(string $type): ?string;
}
```

All DataTypeGuesser implementations are registered with the `DataTypeFacade` in the adminhtml `di.xml` configuration. The order of data type guessers matter - more generic types should come later in the list.

Excerpt of the Hyva_Admin `etc/adminhtml/di.xml` configuration:

```html
<type name="Hyva\Admin\Model\DataType\DataTypeFacade">
    <arguments>
        <argument name="dataTypeClassMap" xsi:type="array">
            <item name="datetime" xsi:type="string">Hyva\Admin\Model\DataType\DateTimeDataType</item>
            <item name="magento_product" xsi:type="string">Hyva\Admin\Model\DataType\ProductDataType</item>
            ...
        </argument>
    </arguments>
</type>
```

Probably you will never need to implement this interface, unless you want to contribute to Hyva_Admin and add support for a standard Magento data type.

## Interface Methods

### valueToTypeCode($value): ?string

The method takes a value that could be anything: null, a string, int, object or anything else.

If the value is the target type the class can handle, then the method returns an identifying column type code, otherwise it should return `null`.

Example:

```php
public function valueToTypeCode($value): ?string
{
    return is_object($value) && $value instanceof AddressInterface
        ? 'magento_customer_address'
        : null;
}
```

In this example the method returns the column type `magento_customer_address` when a customer address instance is used as the input value.

### typeToTypeCode(string $type): ?string

The method takes a Magento internal type identifier. It could be a class or interface name, or an EAV attribute backend type code, or a special case like `gallery`.

If the string `$type` identifies the target type the class can handle, the method should return an identifying column type code, otherwise it should return `null`.

Example:

```php
public function typeToTypeCode(string $type): ?string
{
    return is_string($type) && is_subclass_of($type, AddressInterface::class)
        ? 'magento_customer_address'
        : null;
}
```

In this example the method returns the column type `magento_customer_address` when `$type` identifies a class implementing the customer address interface.
