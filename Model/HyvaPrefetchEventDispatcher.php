<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\HyvaAdminEventContainer;

class HyvaPrefetchEventDispatcher
{
    private EventManagerInterface $eventManager;

    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatch(string $gridName, $data)
    {
        $eventName = 'hyva_grid_source_prefetch_' . $this->getGridNameEventSuffix($gridName);

        return $this->dispatchEvent($gridName, $eventName, $data);
    }

    private function dispatchEvent(string $gridName, string $eventName, $data)
    {
        $container = new HyvaAdminEventContainer($data);

        $this->eventManager->dispatch($eventName, [
            'grid_name' => $gridName,
            'data_container' => $container
        ]);

        return $container->getContainerData();
    }

    private function getGridNameEventSuffix(string $gridName): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $gridName));
    }
}
