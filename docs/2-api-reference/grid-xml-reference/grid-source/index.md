# grid > source

Every grid requires a data source.

There are different types of grid data providers.

Currently supported data providers are:

* repositoryListMethod
* arrayProvider
* collection
* query

More data provider types will be added in future. The next provider type will likely be a SQL query provider.

## Examples:

```html
<source>
    <arrayProvider>HyvaAdminTestModelLogFileListProvider</arrayProvider>
</source>
```

```html
<source>
    <repositoryListMethod>MagentoCatalogApiProductRepositoryInterface::getList</repositoryListMethod>
</source>
```

```html
<source>
    <collection>MagentoCatalogModelResourceModelProductCollection</collection>
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

