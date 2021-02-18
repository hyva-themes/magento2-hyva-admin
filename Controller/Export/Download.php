<?php declare(strict_types = 1);

namespace Hyva\Admin\Controller\Export;

use Hyva\Admin\Model\GridExport\Export;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download implements ActionInterface
{

    private Export $export;

    private RequestInterface $request;

    private FileFactory $fileFactory;

    public function __construct(
        RequestInterface $request,
        FileFactory $fileFactory,
        Export $export)
    {
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