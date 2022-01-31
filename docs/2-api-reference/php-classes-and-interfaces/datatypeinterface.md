# DataTypeInterface

This interface is used to implement most of the built in Magento data types that Hyva_Admin can automatically determine.

It combines the interfaces `HyvaAdminApiDataTypeGuesserInterface` and `HyvaAdminApiDataTypeValueToStringConverterInterface` for convenience.

All implementations must be registered with the `HyvaAdminModelDataTypeDataTypeFacade` in the adminhtml `di.xml` configuration.

Excerpt of the Hyva_Admin `etc/adminhtml/di.xml` configuration:

```html
<type name="HyvaAdminModelDataTypeDataTypeFacade">
    <arguments>
        <!-- note: order matters - generic DataTypes (e.g. object, unknown) come after more specific ones -->
        <argument name="dataTypeClassMap" xsi:type="array">
            <item name="datetime" xsi:type="string">HyvaAdminModelDataTypeDateTimeDataType</item>
            ...
            <item name="magento_tier_price" xsi:type="string">HyvaAdminModelDataTypeTierPriceDataType</item>
            <item name="object" xsi:type="string">HyvaAdminModelDataTypeGenericObjectDataType</item>
            <item name="unknown" xsi:type="string">HyvaAdminModelDataTypeUnknownDataType</item>
        </argument>
    </arguments>
</type>
```

Please refer to the documentation of the `DataTypeGuesserInterface` and the `DataTypeValueToStringConverterInterface` for further information.