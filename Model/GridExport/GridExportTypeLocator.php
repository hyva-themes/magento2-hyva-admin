<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\ObjectManagerInterface;

class GridExportTypeLocator
{
    /**
     * @var array
     */
    private $gridExportTypes;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        array $gridExportTypes,
        ObjectManagerInterface $objectManager
    ) {
        $this->gridExportTypes = $gridExportTypes;
        $this->objectManager   = $objectManager;
    }

    public function getExportType(HyvaGridInterface $grid, string $exportType): ExportTypeInterface
    {
        $export = $grid->getNavigation()->getExports()[$exportType] ?? null;

        if (!$export) {
            $msg = sprintf('Export type "%s" not configured for Hyvä grid "%s"', $exportType, $grid->getGridName());
            throw new \InvalidArgumentException($msg);
        }

        $exportClass = $export->getClass() ?? $this->gridExportTypes[$exportType] ?? null;
        if (!$exportClass) {
            throw new \LogicException(sprintf('Hyva_Admin Grid Export type "%s" is unknown', $exportType));
        }

        return $this->objectManager->create($exportClass, ['grid' => $grid]);
    }
}
