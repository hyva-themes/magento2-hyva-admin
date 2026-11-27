# Using Grid Source Processors

Grid source processors are rarely needed. They allow low level access to the grid data load process, in case the declarative nature of Hyvä admin grids is not sufficient.

If you are only just beginning to use Hyvä admin grids, feel free to skip this page and come back to it later if you are bumping against the boundaries of what can be done in configuration only.

Sometimes the Hyva Admin Grid API is too limited for what you need to do. For example. applying a filter might require a non-standard process because it involves some external entity.

In such cases it’s possible to declare grid source processors. A grid can have any number of grid source processors.

```html
<source>
    <processors>
        <processor class="Hyva\AdminTest\HyvaGridProcessor\ProductGridQueryProcessor"/>
    </processors>
</source>
```

Processors implement the interface `Hyva\Admin\Api\HyvaGridSourceProcessorInterface`.

(There is one extended processor interface for use with collections, the `HyvaGridCollectionProcessorInterface`.  More on that further below).
```php
interface HyvaGridSourceProcessorInterface
{
    /**
     * Provides the ability to mutate the grid $source before the grid data is loaded.
     *
     * @param mixed $source
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     */
    public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void;

    /**
     * Provides the ability to change the raw grid result after it is loaded.
     *
     * @param mixed $rawResult
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     * @return mixed
     */
    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName);
}
```

If only one of the methods is needed, a processor can also extend from

`Hyva\Admin\Model\Grid\Source\AbstractGridSourceProcessor` and override only the needed method.

The effect of processors could also be achieved using events or plugins, but processors are designed to require the least amount of boilerplate code.

## When processors are called

Processors are called every time the grid source loads data. This can happen multiple times during a single request:

  * Once for every search criteria the grid data is loaded with. For example, an export loads the records in batches, and the processors are called for every batch.
  * For array grid sources additionally once for the column detection, before the grid records are loaded. The search criteria of this call has a page size of 1 and no filters or sort orders.

Because of this, **processors need to be idempotent**: calling `beforeLoad` or `afterLoad` again must not change the result.

  * Don't accumulate changes across calls, for example by appending to a list on every call. Set values instead.
  * Side effects that should only happen once per request, like sending a notification, need to be guarded.

The data of array providers is loaded only once per request and then reused for every call, because loading it can be expensive. See the array grid source notes below for what this means for `beforeLoad`.

## Grid Processor method arguments

### $source and $rawResult

The  `beforeLoad` and `afterLoad`, and they receive the grid source instance and the raw data.

As you can see in the interface show above, the `$source` and `$rawResult` arguments are annotated to have the type `mixed`.

This is because they depends on the grid source type that is being used.

*Repository Grid source*

  * `$source`: instance of the class on which the getList method is called
  * `$rawResult`:  `MagentoFrameworkApiSearchResultsInterface` instance
  
*Collection Grid source*

  * `$source`: Collection instance before the search criteria are applied
  * `$rawResult`: Collection instance after the search criteria has been applied (it may already be loaded)

*Array Grid source*

  * `$source`: array provider instance. It's the same instance on every call during a request.
  * `$rawResult`: array result after filtering but before pagination or sorting has been applied

*Query Grid source*

  * `$source`: `MagentoFrameworkDBSelect` instance after search criteria is applied
  * `$rawResult`: Query result array with the structure `['data' => $rows, 'count' => $count]`

### SearchCriteriaInterface $searchCriteria

The `SearchCriteriaInterface` instance with all the filters, pagination and sorting values that should be applied for the current grid view.

The search criteria is only provided for informational purposes - do not change any values on it, as that would cause the grid source to be queried multiple times.

The reason is that the `GridSource` memoizes the grid data depending on the search criteria values.

If the method is called again but the search criteria has changed, the query will be executed again.

### string $gridName

The grid name is supplied for informational reasons, for example so it can be included in exception messages if needed.

## Example use cases:

The use case for afterLoad don’t really depend on the grid source type. afterLoad can always be used to change or enhance the source data.

But what can be done in the beforeLoad method depends a lot on the grid source type. Use cases for the beforeLoad processor method include (but are not limited to):

*Repository Grid source*
  * Set properties on the repository to influence how the search criteria is applied, for example, the store ID. This will probably only be useful for custom repositories, not for repositories that are provided by the core.

*Collection Grid source*

  * Setting additional filters and flags on the collection.

*Array Grid source*
 
  * It really depends on what influence the array grid source provider class allows.
  * The array provider `getHyvaGridData()` method is called only once per request, during the first data load (usually the column detection), right after the `beforeLoad` calls for that load. Changes `beforeLoad` makes to the provider in later calls don't change the grid data. This means changes to the provider can't depend on the filters or sort orders of the grid search criteria. To change the records depending on the grid search criteria, use `afterLoad`, which receives the records with the filters already applied.

*Query Grid source*

  * Set bind values on the select instance, remove and alter any part of the query

  * At the time of writing grid source processors are called in module load order - there is no way to apply a sort order.

## HyvaGridCollectionProcessorInterface

This interface extends the regular `HyvaGridSourceProcessorInterface` with a method that is specific for collections:

```php
public function afterInitSelect(AbstractDbCollection $source, string $gridName): void;
```

This `afterInitSelect` callback that is only applicable to collection grid sources. 

It is called every time the collection grid source is instantiated, before the search criteria is applied. The callback is intended to allow joining additional fields that will then be available as grid columns.
