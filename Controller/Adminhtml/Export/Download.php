<?php declare(strict_types = 1);

namespace Hyva\Admin\Controller\Adminhtml\Export;

use Hyva\Admin\Model\Export;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action implements HttpGetActionInterface
{
    const GRID_NAME = 'gridName';

    protected HyvaGridInterface $grid;

    protected Export $export;

    private RequestInterface $request;

    private FileFactory $fileFactory;

    private ResponseInterface $response;

    public function __construct(
        Context $context,
        RequestInterface $request,
        ResponseInterface $response,
        FileFactory $fileFactory,
        Export $export
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->fileFactory = $fileFactory;
        $this->response = $response;
        $this->export = $export;
    }

    public function execute()
    {
        $export = $this->export->getExport(
            $this->request->getParam(self::GRID_NAME, ''),
            $this->request->getParam('exportType', '')
        );
        $this->prepareRequest();
        $export->create();
        $this->response = $this->fileFactory->create(
            basename($export->getFileName()),
            [
                "type"  => "filename",
                "value" => $export->getFileName(),
                "rm"    => true,
            ],
            $export->getRootDir(),
            $export->getMetaType()
        );
        $this->response->sendResponse();
    }

    private function prepareRequest()
    {
        $params = array_diff_key($this->request->getParams(), array_flip(['p', 'key', 'exportType', 'ajax']));
        $this->request->clearParams()->setParams($params);
    }
}