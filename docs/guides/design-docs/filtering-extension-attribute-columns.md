# Filtering Extension Attribute Columns

By default no columns have filters.

To enable filtering for a column, add a [grid > navigation > filters > filter](../../api-reference/grid-xml-reference/navigation/filters/index.md) element to the grid definition.

The type of filter depends on the column type.

More information on this can be found in the filter node documentation in the Grid XML API reference.

Here I want to note some thoughts on applying custom filter types, for example to allow filtering columns containing extension attributes.

The repository grid provider type dispatches an event `hyva_grid_source_prefetch_` + grid-name whenever grid data is about to be loaded. (Non-alphanumeric characters in the grid name like `-` are turned into underscores so the event name is valid in Magento `events.xml` files).

This is the code in the `RepositoryGridSourceType` class that dispatches the event:

```php
private function dispatchEvent(
    string $gridName,
    string $recordType,
    SearchCriteriaInterface $searchCriteria
): SearchCriteriaInterface {
    $eventName = 'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName):
    $container = new SearchCriteriaEventContainer($searchCriteria);
    $this->eventManager->dispatch($eventName, [
        'search_criteria_container' => $container,
        'grid_name'                 => $gridName,
        'record_type'               => $recordType
    ]);

    return $container->getSearchCriteria();
}
```

Event observers can modify or replace the search criteria, or they can also remember the search criteria values and apply the appropriate values when an extension attribute is loaded after the main record data.

This event may also be used to do other modifications to the `SearchCriteria`, for example mapping attribute codes to internal column names.
