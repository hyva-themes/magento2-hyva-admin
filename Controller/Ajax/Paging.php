<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Ajax;

use Hyva\Admin\Model\GridBlockRenderer;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

class Paging implements ActionInterface
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

    public function __construct(RequestInterface $request, GridBlockRenderer $gridBlockRenderer, JsonResultFactory $jsonResultFactory)
    {
        $this->request = $request;
        $this->gridBlockRenderer = $gridBlockRenderer;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        $this->setOrigRoute($this->request);
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
