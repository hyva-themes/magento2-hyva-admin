# HyvaGridSourceProcessorInterface

This interface is implemented by all grid source processors.

Grid source processors are an advanced tool that is only rarely needed.

```php
/**
 * Implement this interface in your module and configure it on a grid:
 *
 * <grid>
 *   <source>
 *     ... other source config ...
 *     <processors>
 *       <processor class="Your\Module\HyvaGridProcessor\MyGridQueryProcessor"/>
 *     </processors>
 *   </source>
 * </grid>
 *
 * Processors are called every time the grid source loads data, which can happen multiple times during a request:
 * once for each search criteria the data is loaded with (for example for every batch of an export), and for
 * array grid sources also once for the column detection, before the grid records are loaded.
 * Processors should therefore be idempotent: calling them again must not change the result.
 */
interface HyvaGridSourceProcessorInterface
{
    /**
     * Provides the ability to mutate the grid $source before the grid data is loaded.
     *
     * The search criteria will already have been applied (if applicable for a given source type).
     * Do not mutate $searchCriteria since that will cause multiple loads (it's signature changes,
     * seeHyvaAdminModelGridSourceSearchCriteriaIdentity).
     *
     * The type of $source is grid configuration dependent (it might be a Select instance, or a
     * collection, or a repository, ...).
     *
     * @param mixed $source
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     */
    public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void;

    /**
     * Provides the ability to change the raw grid result after it is loaded.
     *
     * The method must return the new result or null. The $result type depends on the grid configuration.
     * If null is returned, the result value from before afterLoad is used.
     *
     * Do not mutate $searchCriteria since that will cause multiple loads (because )it's signature change,
     * see note on beforeLoad above).
     *
     * @param mixed $rawResult
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     * @return mixed
     */
    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName);
}
```

If you require only one of the two methods, consider extending your processor from the `AbstractGridSourceProcessor`, which provides a null-op implementation of the interface, and override only the method you need.

Processors can be called multiple times during a single request, so they need to be idempotent.
More information on when processors are called can be found in the [Grid Source Processors guide](../../guides/hyva-admin-grid-walkthrough/using-grid-source-processors.md#when-processors-are-called).
