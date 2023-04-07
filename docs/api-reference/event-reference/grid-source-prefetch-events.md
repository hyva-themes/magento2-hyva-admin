# Grid Source Prefetch Events

Before the grid data is loaded from the grid source object, two events are dispatched.

* `'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName)`
* `'hyva_grid_source_prefetch'`


The grid name event suffix is basically the grid name in lower case, with underscores instead of non-alphanumeric characters.

For example, a grid named `product-grid` would dispatch the event `hyva_grid_source_prefetch_product_grid`.

Both events are intended to be used to customize the search criteria instance that will be passed to the grid source.

### Event Arguments

The event arguments are

```php
[
    'search_criteria_container' => $container,
    'grid_name'                 => $gridName,
    'record_type'               => $recordType
]
```

The container is an instance of

`Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer`.

The record type argument is the type for the grid records. It will either be a PHP class or interface, or the string `array`, or a database table name, depending on the grid configuration.

The grid name and the record type are passed along with the event for informational purposes, they can not be changed by observers.

### Observers

In an event observer, the search criteria can be updated as shown in this example:

```php
public function execute(Observer $observer)
{
    /** @var SearchCriteriaEventContainer $searchCriteriaContainer */
    $searchCriteriaContainer = $event->getData('search_criteria_container');
    $type = $event->getData('record_type');

    $updatedSearchCriteria = $this->changeCriteria($type, $searchCriteriaContainer->getSearchCriteria());

    $searchCriteriaContainer->replaceSearchCriteria($updatedSearchCriteria);
}
```

