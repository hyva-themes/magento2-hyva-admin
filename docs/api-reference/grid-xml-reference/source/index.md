# grid > source

Every grid requires a data source.

There are different types of grid data providers.

Currently supported data providers are:

* repositoryListMethod
* arrayProvider
* collection
* query

More data provider types will be added in future.

## Examples:

```html
<source>
    <arrayProvider>Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
</source>
```

```html
<source>
    <repositoryListMethod>Magento\Catalog\Api\ProductRepositoryInterface::getList</repositoryListMethod>
</source>
```

```html
<source>
    <collection>Magento\Catalog\Model\ResourceModel\Product\Collection</collection>
</source>
```

```html
<source>
    <query>
        <select>
            <from table="sales_order"/>
        </select>
    </query>
</source>
```

