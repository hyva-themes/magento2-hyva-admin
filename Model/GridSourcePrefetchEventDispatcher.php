<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

use function array_reduce as reduce;

class GridSourcePrefetchEventDispatcher
{
    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatch(
        string $gridName,
        string $recordType,
        SearchCriteriaInterface $searchCriteria
    ): SearchCriteriaInterface {
        return reduce(
            [
                'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName),
                'hyva_grid_source_prefetch',
            ],
            function (
                SearchCriteriaInterface $searchCriteria,
                string $eventName
            ) use ($recordType, $gridName): SearchCriteriaInterface {
                return $this->dispatchEvent($gridName, $recordType, $eventName, $searchCriteria);
            },
            $searchCriteria
        );
    }

    private function dispatchEvent(
        string $gridName,
        string $recordType,
        string $eventName,
        SearchCriteriaInterface $searchCriteria
    ): SearchCriteriaInterface {
        $container = new SearchCriteriaEventContainer($searchCriteria);
        $this->eventManager->dispatch($eventName, [
            'search_criteria_container' => $container,
            'grid_name'                 => $gridName,
            'record_type'               => $recordType,
        ]);

        return $container->getSearchCriteria();
    }

    private function getGridNameEventSuffix(string $gridName): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $gridName));
    }
}
