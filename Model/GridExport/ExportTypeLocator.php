<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

class ExportTypeLocator
{
    /**
     * @var string[]
     */
    private $gridExportTypes;

    /**
     * @var ExportTypeFactory
     */
    private $exportTypeFactory;

    public function __construct(array $gridExportTypes, ExportTypeFactory $exportTypeFactory)
    {
        $this->gridExportTypes   = $gridExportTypes;
        $this->exportTypeFactory = $exportTypeFactory;
    }

    public function get(string $exportType, $args): ExportTypeInterface
    {
        if (isset($this->gridExportTypes[$exportType])) {
            return $this->exportTypeFactory->create($this->gridExportTypes[$exportType], $args);
        }
        throw new \Exception (sprintf('Export type "%s" not defined', $exportType));
    }
}
