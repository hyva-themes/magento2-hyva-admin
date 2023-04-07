# Grid Column Definition Build After Event

Usually columns are customized by configuring them in grid XML.

However, there might be situations where column definitions need to be changed depending on some conditional logic.

The `hyva_grid_column_definition_build_after` event was introduced for this purpose.

After the column definition have been built, based on the grid record type and the grid configuration, this event allows for further programatic customization.

The dispatched event is

```php
'hyva_grid_column_definition_build_after_' . $this->getGridNameEventSuffix($gridName)
```

The grid event name suffix is the grid name in lower case with underscores instead of non-alphanumeric characters. For example a grid named `product-grid` will dispatch events named `hyva_grid_column_definition_build_after_product_grid`.

### Event Arguments

The event arguments are

```php
[
    'grid_name' => $gridName,
    'data_container' => $container
]
```

The grid name is passed along with the event for informational purposes, changing it has no effect.

The data container is an instance of

`Hyva\Admin\Model\GridSourceType\RepositorySourceType\HyvaGridEventContainer`.

The container instance contains an associative array with all grid columns definition instances; the array keys being the column IDs.

### Observers

To update a column definition follow this pattern:

```php
public function execute(Observer $observer)
{
    /** @var \Hyva\Admin\Model\GridSourceType\RepositorySourceType\HyvaGridEventContainer $container */
    /** @var \Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface[] $columnsMap */
    $container = $observer->getData('data_container');
    $columnsMap = $container->getContainerData(); // map of keys to column definitions
    $columnsMap['example'] = $columnsMap['example']->merge(['initiallyHidden' => 'true']);
    $container->replaceContainerData($columnsMap);
}
```

Note: the `ColumnDefinition::merge()` method does not change the existing instance, instead, it returns a new instance with the merged properties applied.

The argument with the properties to merge can be an associative array, or it can be another `ColumnDefinitionInterface` instance.
