<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Export;

use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Model\GridExport\GridExportTypeLocator;
use Hyva\Admin\Model\GridExport\HyvaGridExportInterfaceFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action implements HttpGetActionInterface
{
    /**
     * @var GridExportTypeLocator
     */
    private $gridExportLocator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var HyvaGridExportInterfaceFactory
     */
    private $gridFactory;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var GridAuthorization
     */
    private $gridAuthorization;

    public function __construct(
        Context $context,
        RequestInterface $request,
        FileFactory $fileFactory,
        GridExportTypeLocator $gridExportLocator,
        HyvaGridExportInterfaceFactory $gridFactory,
        ?GridAuthorization $gridAuthorization = null
    ) {
        parent::__construct($context);
        $this->request           = $request;
        $this->fileFactory       = $fileFactory;
        $this->gridExportLocator = $gridExportLocator;
        $this->gridFactory       = $gridFactory;
        $this->context           = $context;
        // The new dependency is optional for backward compatibility with classes extending this controller
        $this->gridAuthorization = $gridAuthorization ?? ObjectManager::getInstance()->get(GridAuthorization::class);
    }

    /**
     * Only export grids the admin user is allowed to see and that were rendered during the admin session.
     */
    protected function _isAllowed()
    {
        $gridName = $this->getRequest()->getParam('gridName', '');

        return parent::_isAllowed()
            && is_string($gridName)
            && $this->gridAuthorization->isAllowedForEndpoint($gridName);
    }

    public function execute()
    {
        $grid   = $this->gridFactory->create(['gridName' => $this->request->getParam('gridName', '')]);
        $export = $this->gridExportLocator->getExportType($grid, $this->request->getParam('exportType', ''));
        $export->createFileToDownload();
        $response = $this->fileFactory->create(
            basename($export->getFileName()),
            [
                "type"  => "filename",
                "value" => $export->getFileName(),
                "rm"    => true,
            ],
            $export->getExportDir(),
            $export->getContentType()
        );
        return $response;
    }

}
