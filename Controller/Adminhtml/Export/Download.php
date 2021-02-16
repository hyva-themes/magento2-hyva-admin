<?php declare(strict_types = 1);

namespace Hyva\Admin\Controller\Adminhtml\Export;

use Hyva\Admin\Model\GridExport\Export;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action implements HttpGetActionInterface
{

    private Export $export;

    private RequestInterface $request;

    private FileFactory $fileFactory;

    public function __construct(
        Context $context,
        RequestInterface $request,
        FileFactory $fileFactory,
        Export $export
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->fileFactory = $fileFactory;
        $this->export = $export;
    }

    public function execute()
    {
        $exportType = $this->export->getExportType(
            $this->request->getParam('gridName', ''),
            $this->request->getParam('exportType', '')
        );
        $exportType->createFileToDownload();
        $response = $this->fileFactory->create(
            basename($exportType->getFileName()),
            [
                "type"  => "filename",
                "value" => $exportType->getFileName(),
                "rm"    => true,
            ],
            $exportType->getRootDir(),
            $exportType->getContentType()
        );
        $response->sendResponse();
    }

}