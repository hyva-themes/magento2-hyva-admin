<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\HyvaAdminEventContainer;

use function array_reduce as reduce;

class HyvaPrefetchEventDispatcher
{
    private EventManagerInterface $eventManager;

    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatch(
        string $gridName,
        $data
    ): array {
        return reduce(
            [
                'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName),
            ],
            fn (
                $data,
                string $eventName
            ) => $this->dispatchEvent($gridName, $eventName, $data),
            $data
        );
    }

    private function dispatchEvent(
        string $gridName,
        string $eventName,
        array $data
    ) {
        $container = new HyvaAdminEventContainer($data);

        $this->eventManager->dispatch($eventName, [
            'grid_name'       => $gridName,
            'data_container'  => $container
        ]);

        return $container->getContainerData();
    }

    private function getGridNameEventSuffix(string $gridName): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $gridName));
    }
}
