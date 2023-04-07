# Setting a Grid Data Source

Every grid needs data to display.

Hyva_Admin grids try to make it as simple as possible to take existing data and display it in a grid.

Sources can be different types. Currently Hyva_Admin supports the following grid source types:

* Array Grid Source Type
* Repository Grid Source Type
* Collection Grid Source Type
* Query Grid Source Type

The source type is configured in the grid XML file in a `<source>` element.

The child element determines the type of provider

## Array Grid Source

To set an array provider, use the `<arrayProvider>` element:

```html
<source>
    <arrayProvider>Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
</source>
```

Array providers have to implement the interface `Hyva\Admin\Api\HyvaGridArrayProviderInterface`.

More details on array providers can be found in the PHP Class API reference documentation.

Array providers return the full data. Pagination, filtering and sorting is applied in later by Hyva_Admin.

In future another array provider variation might be added that allows handling pagination directly, so it can work with efficiently with large data sets. Currently a collection of repository provider needs to be used for this.

## Repository Grid Source

To set a repository source type, use the `<repositoryListMethod>` element:

```html
<source>
    <repositoryListMethod>\Magento\Customer\Api\CustomerRepositoryInterface::getList</repositoryListMethod>
</source>
```

The name of the method doesn’t have to be `getList`. The important thing here is that the specified method takes a `Magento\Framework\Api\SearchCriteriaInterface` as an argument, and returns a `Magento\Framework\Api\SearchResultsInterface` like thing.

In the core code convention is to name these methods `getList`, but you can use whatever name you want on custom repositories, as long as the input and output are the same.

## Collection Grid Source

Next there is the collection source type. It is specified with the collection element:

```html
<source>
    <collection>Magento\Customer\Model\ResourceModel\Customer\Collection</collection>
</source>
```

The collection has to be a DB collection, because that is how the sorting, paging and filtering is applied. That said, more basic collections are very rare, so most should “just work”.

## Query Grid Source

Finally there is the query source type.

The query source type allows configuring a SQL select query in the grid XML. The result is displayed directly in the grid, without the need for any custom PHP classes.

```html
<source>
    <query>
        <select>
            <from table="sales_order"/>
            <columns>
                <column name="status"/>
                <column name="state"/>
                <column name="created_at" as="latest_order"/>
                <expression as="count">COUNT(*)</expression>
            </columns>
            <groupBy>
                <column name="status"/>
                <column name="state"/>
            </groupBy>
        </select>
    </query>
</source>
```

At the time of writing query source providers support select expressions, join, group by and, union select.

Pagination and sorting can be applied by configuring default search criteria bindings (see the next page in this walkthrough).

### Choosing the right source for a grid

Often we can choose between a repository list method, a collection and maybe even another grid  collection. For example, for Magento orders the choices are:

* `Magento\Sales\Api\OrderRepositoryInterface::getList`
* `Magento\Sales\Model\ResourceModel\Order\Collection`
* `Magento\Sales\Model\ResourceModel\OrderGridCollection`

Which is best?

As always the answer is, it depends.

Most of the time it doesn’t matter. Just use what you have at hand and see if it is good enough.

Sometimes a repository returns entities with more columns that are not present on regular models. These might be extension attributes that are not loaded on the regular models.

Other times grid collections contain aggregate fields that aren’t available on regular collections or with entities loaded with a repository.

This is the case for the order grid collection, where the index table contains a field for the full customer name. The regular order model only contains separate fields for the customer first and last name.

A column containing the full name can make filtering for a specific customer name more convenient.

### Future provider types:

More grid providers will be added and the existing ones improved, as the need arrises.

If you implement a grid provider you find useful, please open a merge request!
