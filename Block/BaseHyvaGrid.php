<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;

abstract class BaseHyvaGrid extends Template
{
    private HyvaGridInterfaceFactory $gridFactory;

    private array $children;

    private HyvaGridInterface $grid;

    public function __construct(
        Template\Context $context,
        string $gridTemplate,
        HyvaGridInterfaceFactory $gridFactory,
        array $children = [],
        array $data = []
    ) {
        $this->setTemplate($gridTemplate);
        parent::__construct($context, $data);
        $this->gridFactory = $gridFactory;
        $this->children = $children;
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
        $renderer = $this->createRenderer("navigation");
        $renderer->assign('navigation', $this->getGrid()->getNavigation());
        return $renderer->toHtml();
    }

    public function getActionsHtml(): string
    {
        $renderer = $this->createRenderer("actions");
        $renderer->assign('actions', $this->getGrid()->getActions());
        return $renderer->toHtml();
    }

    public function getExportsHtml(): string
    {
        $renderer = $this->createRenderer("exports");
        $renderer->assign('exports', $this->getGrid()->getNavigation()->getExports());
        return $renderer->toHtml();
    }

    private function createRenderer($name): Template {
        if ($blockDefinition = $this->children[$name] ?? false) {
            /** @var Template $renderer */
            $renderer = $this->_layout->createBlock(Template::class);
            if (!isset($blockDefinition['template'])) {
                throw new \InvalidArgumentException('Child template missing');
            }
            $renderer->setTemplate($blockDefinition['template']);

            if (isset($blockDefinition['view_model'])) {
                if (!$blockDefinition['view_model'] instanceof ArgumentInterface) {
                    throw new \InvalidArgumentException('ViewModel should be instance of ArgumentInterface');
                }
                $renderer->assign('view_model', $blockDefinition['view_model']);
            }
            return $renderer;
        }
        throw new \InvalidArgumentException(sprintf("Block %s not found", $name));
    }
}
