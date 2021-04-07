<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class EntityDefinition implements EntityDefinitionInterface
{
    /**
     * @var string
     */
    private $gridName;

    /**
     * @var array
     */
    private $entityDefinition;

    public function __construct(string $gridName, array $entityDefinition)
    {
        $this->gridName         = $gridName;
        $this->entityDefinition = $entityDefinition;
    }

    public function getLabelPlural(): string
    {
        return $this->entityDefinition['label']['plural'] ?? $this->getLabelSingular() . 's';
    }

    public function getLabelSingular(): string
    {
        return $this->entityDefinition['label']['singular'] ?? $this->gridName . ' record';
    }
}
