# grid > source > arrayProvider

The `arrayProvider` node content has to be a fully qualified PHP the class name.

The class has to implement the `Hyva\Admin\Api\HyvaGridArrayProviderInterface` interface.

```php
interface HyvaGridArrayProviderInterface
{
    /**
     * @return array[]
     */
    public function getHyvaGridData(): array;
}
```

The method returns an array of records. Each record is a sub array that will be rendered as a row in the grid.

The columns are determined based on the array keys of the first record.

## Example:

```html
<source>
    <arrayProvider>Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
</source>
```

