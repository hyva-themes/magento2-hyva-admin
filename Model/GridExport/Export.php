<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

use function array_filter as filter;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;

class Export
{
    /**
     * @var HyvaGridInterfaceFactory
     */
    private $gridFactory;

    /**
     * @var ExportTypeLocator
     */
    private $exportTypeDefinition;

    public function __construct(HyvaGridInterfaceFactory $gridFactory, ExportTypeLocator $exportTypeDefinition)
    {
        $this->gridFactory          = $gridFactory;
        $this->exportTypeDefinition = $exportTypeDefinition;
    }

    public function getExportType(string $gridName, string $exportType): ExportTypeInterface
    {
        $grid    = $this->getGrid($gridName);
        $exports = $grid->getNavigation()->getExports();

        /**  @var $export GridExportInterface */
        $export = current(filter($exports, function (GridExportInterface $export) use ($exportType) {
            return $export->getId() === $exportType;
        }));
        if (!$export) {
            throw new \InvalidArgumentException(sprintf('Export type "%s" not defined', $exportType));
        }
        return $this->exportTypeDefinition->get($export->getId(), [
            'grid'     => $grid,
            'fileName' => $export->getFileName() ?: ($gridName . '.' . $export->getId()),
        ]);
    }

    private function getGrid(string $name): HyvaGridInterface
    {
        return $this->gridFactory->create(['gridName' => $name]);
    }

}
