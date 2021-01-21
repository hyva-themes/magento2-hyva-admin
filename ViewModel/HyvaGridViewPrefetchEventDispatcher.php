<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

use function array_reduce as reduce;

class HyvaGridViewPrefetchEventDispatcher
{
    private EventManagerInterface $eventManager;

    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatch(
        string $gridName,
        array $columnDefinitions
    ): array
    {
        return reduce(
            [
                'hyva_column_definition_prefetch_' . $this->getGridNameEventSuffix($gridName),
                'hyva_column_definition_prefetch',
            ],
            fn (
                array $columnDefinitions,
                string $eventName
            ): array => $this->dispatchEvent($gridName, $eventName, $columnDefinitions),
            $columnDefinitions
        );
    }

    private function dispatchEvent(
        string $gridName,
        string $eventName,
        array $columnDefinitions
    ): array {
        $this->eventManager->dispatch($eventName, [
            'column_definitions' => $columnDefinitions,
            'grid_name'          => $gridName
        ]);

        return $columnDefinitions;
    }

    private function getGridNameEventSuffix(string $gridName): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $gridName));
    }
}
