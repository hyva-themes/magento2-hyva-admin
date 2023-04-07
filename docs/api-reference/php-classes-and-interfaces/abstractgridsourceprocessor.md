# AbstractGridSourceProcessor

The  `Hyva\Admin\Model\GridSource\AbstractGridSourceProcessor` can be extended to allow low level access to the grid source and the grid result.

```php
/**
 * Generic parent implementation of HyvaGridSourceProcessorInterface.
 *
 * Extend this class if you don't need both methods in your processor.
 *
 * If extending this class, you don't need to also call this parent class methods.
 */
abstract class AbstractGridSourceProcessor implements HyvaGridSourceProcessorInterface
{
    public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
    {

    }

    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
    {
        return $rawResult;
    }
}
```

It is enough to implement the `HyvaGridSourceProcessorInterface` when building custom processors, but if only one of the methods is needed, extending the `AbstractGridSourceProcessor` will allow overriding only the method that is needed, and using the default null-op implementation for the other.

More information on grid source processors can be found in the Grid Walkthrough documentation.
