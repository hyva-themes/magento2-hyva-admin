<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Ajax;

use Hyva\Admin\Model\GridBlockRenderer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

class Paging extends Action implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var GridBlockRenderer
     */
    private $gridBlockRenderer;

    /**
     * @var JsonResultFactory
     */
    private $jsonResultFactory;

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
            $this->setOrigRoute($this->request);
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

    private function setOrigRoute(RequestInterface $request): void
    {
        if ($request instanceof \Magento\Framework\App\Request\Http) {
            $origRoute = explode('/', $request->getParam('origRoute', ''));
            if ($origRoute[0] ?? false) {
                $request->setRouteName($origRoute[0]);
            }
            if ($origRoute[1] ?? false) {
                $request->setControllerName($origRoute[1]);
            }
            if ($origRoute[2] ?? false) {
                $request->setActionName($origRoute[2]);
            }
        }
    }
}
