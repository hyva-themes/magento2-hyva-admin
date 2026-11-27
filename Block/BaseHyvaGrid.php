<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Model\TemplateDirectiveState;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

abstract class BaseHyvaGrid extends Template
{
    /**
     * @var HyvaGridInterfaceFactory
     */
    private $gridFactory;

    /**
     * @var GridAuthorization|null
     */
    private $gridAuthorization;

    /**
     * @var TemplateDirectiveState|null
     */
    private $templateDirectiveState;

    public function __construct(
        Template\Context $context,
        string $gridTemplate,
        HyvaGridInterfaceFactory $gridFactory,
        array $data = [],
        ?GridAuthorization $gridAuthorization = null,
        ?TemplateDirectiveState $templateDirectiveState = null
    ) {
        $this->setTemplate($gridTemplate);
        parent::__construct($context, $data);
        $this->gridFactory            = $gridFactory;
        $this->gridAuthorization      = $gridAuthorization;
        $this->templateDirectiveState = $templateDirectiveState;
    }

    /**
     * Grids are never rendered through template directives like {{block}} or {{layout}} or in emulated areas.
     * Otherwise, admin users with access to email template previews, the CMS WYSIWYG editor or Page Builder could
     * render any grid, or have it recorded for the ajax paging and export endpoints.
     *
     * In the admin, grids are only rendered if the admin user is allowed to access the grid, and rendered grids are
     * recorded, so the ajax paging and export endpoints can serve them to the admin user.
     */
    public function toHtml()
    {
        $gridName = $this->getSanitizedGridName();
        if ($gridName === '') {
            return parent::toHtml();
        }
        if ($this->getTemplateDirectiveState()->isProcessingDirectives() || $this->_appState->isAreaCodeEmulated()) {
            return '';
        }
        if (!$this->isAdminArea()) {
            return parent::toHtml();
        }
        if (!$this->getGridAuthorization()->isAllowed($gridName)) {
            return '';
        }

        $html = parent::toHtml();
        if ($html !== '') {
            $this->getGridAuthorization()->registerRenderedGrid($gridName);
        }

        return $html;
    }

    public function getGrid(): HyvaGridInterface
    {
        $gridName = $this->getSanitizedGridName();
        if (!$gridName) {
            $msg = 'The name of the hyvä grid needs to be set on the block instance.';
            throw new \LogicException($msg);
        }

        return $this->gridFactory->create(['gridName' => $gridName]);
    }

    protected function getSanitizedGridName(): string
    {
        return str_replace(['/', '\\', '.'], '', $this->_getData('grid_name') ?? $this->getNameInLayout() ?? '');
    }

    private function isAdminArea(): bool
    {
        try {
            return $this->_appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (LocalizedException $exception) {
            return false;
        }
    }

    // The dependencies are optional constructor arguments for backward compatibility with classes extending this block

    private function getGridAuthorization(): GridAuthorization
    {
        if (!$this->gridAuthorization) {
            $this->gridAuthorization = ObjectManager::getInstance()->get(GridAuthorization::class);
        }
        return $this->gridAuthorization;
    }

    private function getTemplateDirectiveState(): TemplateDirectiveState
    {
        if (!$this->templateDirectiveState) {
            $this->templateDirectiveState = ObjectManager::getInstance()->get(TemplateDirectiveState::class);
        }
        return $this->templateDirectiveState;
    }
}
