# HyvaGridCollectionProcessorInterface

This interface extends the regular `HyvaGridSourceProcessorInterface`. It provides an `afterInitSelect` callback that is only applicable to collection grid sources.

```php
public function afterInitSelect(MagentoFrameworkDataCollectionAbstractDb $source,
    string $gridName
): void;
```

This callback is triggered every time the collection grid source is instantiated, before the search criteria is applied. It is intended to allow joining additional fields that will then be available as grid columns.

These fields then will be available as grid columns.

To use it, configure a grid source processor as usual and implement the interface in addition to extending the processor from `Hyva\Admin\Model\GridSource\AbstractGridSourceProcessor`.

### Example:

This is an example collection grid source processor as declared in an integration test:

```php
new class() extends AbstractGridSourceProcessor implements HyvaGridCollectionProcessorInterface {

    public function afterInitSelect(AbstractDbCollection $source, string $gridName): void
    {
        $select = $source->getSelect();

        // select expression
        $select->columns(['foo' => newZend_Db_Expr('foo')]);

        // select field from joined table
        $source->getSelect()->joinLeft(
            'catalog_category_product',
            'e.entity_id = catalog_category_product.product_id',
            ['test_field' => 'catalog_category_product.entity_id']
        );
    }
};
```

