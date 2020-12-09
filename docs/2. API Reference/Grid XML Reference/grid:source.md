# grid:source

Every grid requires a data source.

There are different types of grid data providers.

Currently supported data providers are:


* repositoryListMethod
* arrayProvider


More data provider types will be added in future. The next provider types will likely be a collection provider and a SQL query provider.


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


