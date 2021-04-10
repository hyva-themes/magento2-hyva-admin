<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Export;

use Hyva\Admin\Model\GridExport\GridExportTypeLocator;
use Hyva\Admin\Model\GridExport\HyvaGridExportInterfaceFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action implements HttpGetActionInterface
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

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        Context $context,
        RequestInterface $request,
        FileFactory $fileFactory,
        GridExportTypeLocator $gridExportLocator,
        HyvaGridExportInterfaceFactory $gridFactory
    ) {
        parent::__construct($context);
        $this->request           = $request;
        $this->fileFactory       = $fileFactory;
        $this->gridExportLocator = $gridExportLocator;
        $this->gridFactory       = $gridFactory;
        $this->context           = $context;
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
