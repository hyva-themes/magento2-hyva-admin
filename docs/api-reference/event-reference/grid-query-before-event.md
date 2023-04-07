# Grid Query Before Event

This query is dispatched for grids using an grid source type.

First, a `Magento\Framework\DB\Select` instance is created and the query configuration from the grid XML configuration is applied. (Note: `Magento\Framework\DB\Select` is just a small wrapper for `Zend_Db_Select`, the API is the same).

Then then filters, sorting and pagination are applied.

Finally, right before the SQL query is executed, the

`'hyva_grid_query_before_' . $this->getGridNameEventSuffix($this->gridName);`

event is dispatched.

This allows to modify or even completely replace the select that is used to load the grid data.

### Event Arguments

The event arguments are:

```php
[
    'select_container' => $container,
    'grid_name'        => $this->gridName,
]
```

The container is an instance of

`Hyva\Admin\Model\Grid\Source\Type\QueryGridSource\Type\DbSelectEventContainer`.

### Observers

Observers can modify or replace the select instance as follows:

```php
public function execute(Observer $observer)
{
    /** @var DbSelectEventContainer $container */
    $container = $observer->getData('select_container');
    $select    = $container->getSelect();

    $updatedSelect = $this->modifyQuery($select);

    $container->replaceSelect($updatedSelect);
}
```
