# grid > source > collection

The `collection` node content has to be a fully qualified PHP method with a DB collection class name, meaning the collection has to be a child of the class `Magento\Framework\Data\Collection\AbstractDb` (probably with some other intermediate classes in between).

The collection grid source type uses reflection to determine the fields available on the records.

System attributes (a.k.a properties with getter methods) and custom EAV attributes are all displayed as array columns.

## Example:

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <collection>Magento\Customer\Model\ResourceModel\Customer\Collection</collection>
    </source>
</grid>
```

