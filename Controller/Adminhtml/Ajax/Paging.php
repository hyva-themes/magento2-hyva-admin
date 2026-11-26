<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Ajax;

use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Model\GridBlockRenderer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Psr\Log\LoggerInterface;

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

    /**
     * @var GridAuthorization
     */
    private $gridAuthorization;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        RequestInterface $request,
        JsonResultFactory $jsonResultFactory,
        GridBlockRenderer $gridBlockRenderer,
        ?GridAuthorization $gridAuthorization = null,
        ?AppState $appState = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($context);

        $this->request           = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->gridBlockRenderer = $gridBlockRenderer;
        // The new dependencies are optional for backward compatibility with classes extending this controller
        $this->gridAuthorization = $gridAuthorization ?? ObjectManager::getInstance()->get(GridAuthorization::class);
        $this->appState          = $appState ?? ObjectManager::getInstance()->get(AppState::class);
        $this->logger            = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    public function execute()
    {
        $json     = $this->jsonResultFactory->create();
        $gridName = $this->request->getParam('gridName', '');
        $gridName = is_string($gridName) ? $gridName : '';

        if (!$this->gridAuthorization->isAllowedForEndpoint($gridName)) {
            $json->setHttpResponseCode(403);
            return $json->setData([
                'grid_html' => '',
                'message'   => (string) __('Access to this grid is denied. Please reload the page.'),
            ]);
        }

        try {
            $this->setOrigRoute($this->request);
            $result = [
                'grid_html' => $this->gridBlockRenderer->renderGrid($gridName),
                'message'   => null,
            ];
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            $result = [
                'grid_html' => '',
                'message'   => $this->appState->getMode() === AppState::MODE_DEVELOPER
                    ? $exception->getMessage()
                    : (string) __('The grid could not be loaded.'),
            ];
        }

        return $json->setData($result);
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
