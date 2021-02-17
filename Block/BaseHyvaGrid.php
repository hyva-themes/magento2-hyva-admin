<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\View\Element\Template;

abstract class BaseHyvaGrid extends Template
{
    private HyvaGridInterfaceFactory $gridFactory;

    private HyvaGridInterface $grid;

    public function __construct(
        Template\Context $context,
        string $gridTemplate,
        HyvaGridInterfaceFactory $gridFactory,
        array $data = []
    ) {
        $this->setTemplate($gridTemplate);
        parent::__construct($context, $data);
        $this->gridFactory = $gridFactory;
    }

    public function getGrid(): HyvaGridInterface
    {
        if (!isset($this->grid)) {
            $gridName = str_replace(['/', '\\', '.'], '', $this->_getData('grid_name') ?? $this->getNameInLayout());
            if (!$gridName) {
                $msg = 'The name of the hyvä grid needs to be set on the block instance.';
                throw new \LogicException($msg);
            }

            $this->grid = $this->gridFactory->create(['gridName' => $this->_getData('grid_name')]);
        }
        return $this->grid;
    }

    public function getNavigationHtml(): string
    {
        $renderer = $this->createRenderer();
        $renderer->setTemplate('Hyva_Admin::element/navigation.phtml');
        $renderer->assign('navigation', $this->getGrid()->getNavigation());
        return $renderer->toHtml();
    }

    public function getActionsHtml(): string
    {
        $renderer = $this->createRenderer();
        $renderer->setTemplate('Hyva_Admin::element/actions.phtml');
        $renderer->assign('actions', $this->getGrid()->getActions());
        return $renderer->toHtml();
    }

    public function getExportsHtml(): string
    {
        $renderer = $this->createRenderer();
        $renderer->setTemplate('Hyva_Admin::element/exports.phtml');
        $renderer->assign('exports', $this->getGrid()->getNavigation()->getExports());
        return $renderer->toHtml();
    }

    private function createRenderer(): Template {
        return $this->_layout->createBlock(Template::class);
    }
}
