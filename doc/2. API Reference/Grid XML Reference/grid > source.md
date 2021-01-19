# grid > source

Every grid requires a data source.

There are different types of grid data providers.

Currently supported data providers are:


* repositoryListMethod
* arrayProvider
* collection


More data provider types will be added in future. The next provider type will likely be a SQL query provider.


## Examples:


```markup
<source>
    <arrayProvider>\Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
</source>
```


```markup
<source>
    <repositoryListMethod>\Magento\Catalog\Api\ProductRepositoryInterface::getList</repositoryListMethod>
</source>
```


```markup
<source>
    <collection>\Magento\Catalog\Model\ResourceModel\Product\Collection</collection>
</source>
```


