<?php

namespace Hyva\Admin\Model\GridExport;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;

class Export
{

    private HyvaGridInterfaceFactory $gridFactory;

    private TypeDefinition $exportTypeDefinition;

    public function __construct(HyvaGridInterfaceFactory $gridFactory, TypeDefinition $exportTypeDefinition)
    {
        $this->gridFactory = $gridFactory;
        $this->exportTypeDefinition = $exportTypeDefinition;
    }

    /**
     * @param string $gridName
     * @param string $type
     * @return TypeInterface
     */
    public function getExportType(string $gridName, string $type): TypeInterface
    {
        $grid = $this->getGrid($gridName);
        $exports = $grid->getNavigation()->getExports();
        /**
         * @var $export GridExportInterface
         */
        $export = current(array_filter($exports, function ($value) use ($type) {
            return $value->getId() == $type;
        }));
        if (!$export) {
            throw new \InvalidArgumentException(sprintf('Export type "%s" not defined', $type));
        }
        return $this->exportTypeDefinition->get($export->getId(), [
            'grid'     => $grid,
            'fileName' => $export->getFileName() ?: ($gridName . "." . $export->getId()),
        ]);
    }

    private function getGrid(string $name): HyvaGridInterface
    {
        return $this->gridFactory->create(['gridName' => $name]);
    }

}