# grid > source > repositoryListMethod

The `repositoryListMethod` node content has to be a fully qualified PHP method with the class name. The method has to take a `Magento\Framework\Api\SearchCriteriaInterface` as an argument, and return a `Magento\Framework\Api\SearchResultsInterface` .

The method name does not have to be `getList`, even though that is the most common name for this type of method in Magento.

The repository grid source type uses reflection to determine the fields available on the records.

System attributes, custom EAV attributes and extension attributes are all displayed as array columns.

## Example:

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <repositoryListMethod>Magento\Catalog\Api\ProductRepositoryInterface::getList</repositoryListMethod>
    </source>
</grid>
```

