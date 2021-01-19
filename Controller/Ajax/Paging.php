<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Ajax;

use Hyva\Admin\Model\GridBlockRenderer;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

class Paging implements ActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Hyva\Admin\Model\GridBlockRenderer
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
