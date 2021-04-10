<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Export;

use Hyva\Admin\Model\GridExport\GridExportTypeLocator;
use Hyva\Admin\Model\GridExport\HyvaGridExportInterfaceFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download implements ActionInterface
{
    /**
     * @var GridExportTypeLocator
     */
    private $gridExportLocator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var HyvaGridExportInterfaceFactory
     */
    private $gridFactory;

    public function __construct(
        RequestInterface $request,
        FileFactory $fileFactory,
        GridExportTypeLocator $gridExportLocator,
        HyvaGridExportInterfaceFactory $gridFactory
    ) {
        $this->request           = $request;
        $this->fileFactory       = $fileFactory;
        $this->gridExportLocator = $gridExportLocator;
        $this->gridFactory       = $gridFactory;
    }

    public function execute()
    {
        $grid   = $this->gridFactory->create(['gridName' => $this->request->getParam('gridName', '')]);
        $export = $this->gridExportLocator->getExportType($grid, $this->request->getParam('exportType', ''));
        $export->createFileToDownload();
        $response = $this->fileFactory->create(
            basename($export->getFileName()),
            [
                "type"  => "filename",
                "value" => $export->getFileName(),
                "rm"    => true,
            ],
            $export->getExportDir(),
            $export->getContentType()
        );
        return $response;
    }

}
