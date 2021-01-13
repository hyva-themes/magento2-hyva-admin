<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Ajax;

use Hyva\Admin\Model\GridBlockRenderer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

class Paging extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    private RequestInterface $request;

    private GridBlockRenderer $gridBlockRenderer;

    private JsonResultFactory $jsonResultFactory;

    public function __construct(
        Context $context,
        RequestInterface $request,
        JsonResultFactory $jsonResultFactory,
        GridBlockRenderer $gridBlockRenderer
    ) {
        parent::__construct($context);

        $this->request           = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->gridBlockRenderer = $gridBlockRenderer;
    }

    public function execute()
    {
        try {
            $result = [
                'grid_html' => $this->gridBlockRenderer->renderGrid($this->request->getParam('gridName', '')),
                'message'   => null,
            ];
        } catch (\Exception $exception) {
            $result = [
                'grid_html' => '',
                'message'   => $exception->getMessage(),
            ];
        }
        $json = $this->jsonResultFactory->create();
        $json->setData($result);

        return $json;
    }
}
