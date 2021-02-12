<?php declare(strict_types = 1);

namespace Hyva\Admin\Controller\Adminhtml\Export;

use Hyva\Admin\Model\GridExport;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action implements HttpGetActionInterface
{

    private GridExport $export;

    private RequestInterface $request;

    private FileFactory $fileFactory;

    public function __construct(
        Context $context,
        RequestInterface $request,
        FileFactory $fileFactory,
        GridExport $export
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
        $this->prepareRequestForNavigationSearchCriteriaConstruction();
        $exportType->create();
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

    private function prepareRequestForNavigationSearchCriteriaConstruction()
    {
        $params = array_diff_key($this->request->getParams(), array_flip(['p', 'key', 'exportType', 'ajax']));
        $this->request->clearParams()->setParams($params);
    }
}