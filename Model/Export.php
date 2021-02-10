<?php
/**
 * Export
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;

class Export
{

    const GRID_NAME = 'gridName';

    protected array $grid = [];

    private ExportInterfaceFactory $exportInterfaceFactory;

    private HyvaGridInterfaceFactory $gridFactory;

    public function __construct(HyvaGridInterfaceFactory $gridFactory, ExportInterfaceFactory $exportInterfaceFactory)
    {
        $this->gridFactory = $gridFactory;
        $this->exportInterfaceFactory = $exportInterfaceFactory;
    }

    /**
     * @param $gridName
     * @param $type
     * @return ExportInterface
     */
    public function getExport($gridName, $type): ExportInterface
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
        $exportModel = $this->exportInterfaceFactory->create($export->getClassName());
        $exportModel->setGrid($grid)
            ->setFileName($export->getFileName() ?: $exportModel->getFileName() ?: $grid->getGridName());
        return $exportModel;
    }

    public function getGrid($name): HyvaGridInterface
    {
        if (!isset($this->grid[$name])) {
            $this->grid[$name] = $this->gridFactory->create([self::GRID_NAME => $name]);
        }
        return $this->grid[$name];
    }

}