<?php

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;

class GridExport
{

    const GRID_NAME = 'gridName';

    private ExportInterfaceFactory $exportInterfaceFactory;

    private HyvaGridInterfaceFactory $gridFactory;

    public function __construct(HyvaGridInterfaceFactory $gridFactory, ExportInterfaceFactory $exportInterfaceFactory)
    {
        $this->gridFactory = $gridFactory;
        $this->exportInterfaceFactory = $exportInterfaceFactory;
    }

    /**
     * @param string $gridName
     * @param string $type
     * @return ExportInterface
     */
    public function getExportType(string $gridName, string $type): ExportInterface
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
            throw new \InvalidArgumentException("Export type " . $type . " not defined");
        }
        return $this->exportInterfaceFactory->create($export->getClassName(), [
            'gird' => $grid,
            'fileName' => $export->getFileName() ?? $gridName
        ]);
    }

    private function getGrid(string $name): HyvaGridInterface
    {
        return $this->gridFactory->create([self::GRID_NAME => $name]);
    }

}