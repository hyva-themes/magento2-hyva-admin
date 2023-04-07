# DataTypeInterface

This interface is used to implement most of the built in Magento data types that Hyva_Admin can automatically determine.

It combines the interfaces `Hyva\Admin\Api\DataTypeGuesserInterface` and `Hyva\Admin\Api\DataTypeValueToStringConverterInterface` for convenience.

All implementations must be registered with the `Hyva\Admin\Model\DataType\DataTypeFacade` in the adminhtml `di.xml` configuration.

Excerpt of the Hyva_Admin `etc/adminhtml/di.xml` configuration:

```html
<type name="Hyva\Admin\Model\DataType\DataTypeFacade">
    <arguments>
        <!-- note: order matters - generic DataTypes (e.g. object, unknown) come after more specific ones -->
        <argument name="dataTypeClassMap" xsi:type="array">
            <item name="datetime" xsi:type="string">Hyva\Admin\Model\DataType\DateTimeDataType</item>
            ...
            <item name="magento_tier_price" xsi:type="string">Hyva\Admin\Model\DataType\TierPriceDataType</item>
            <item name="object" xsi:type="string">Hyva\Admin\Model\DataType\GenericObjectDataType</item>
            <item name="unknown" xsi:type="string">Hyva\Admin\Model\DataType\UnknownDataType</item>
        </argument>
    </arguments>
</type>
```

Please refer to the documentation of the `DataTypeGuesserInterface` and the `DataTypeValueToStringConverterInterface` for further information.
