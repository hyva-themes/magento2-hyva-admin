<?php declare(strict_types = 1);

namespace Hyva\Admin\Controller\Export;

use Hyva\Admin\Model\GridExport;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download implements ActionInterface
{

    private GridExport $export;

    private RequestInterface $request;

    private FileFactory $fileFactory;

    public function __construct(
        RequestInterface $request,
        FileFactory $fileFactory,
        GridExport $export)
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
            $exportType->getMetaType()
        );
        $response->sendResponse();
    }

    private function prepareRequestForNavigationSearchCriteriaConstruction()
    {
        $params = array_diff_key($this->request->getParams(), array_flip(['p', 'key', 'exportType', 'ajax']));
        $params['pageSize'] = 200;
        $this->request->clearParams()->setParams($params);
    }
}