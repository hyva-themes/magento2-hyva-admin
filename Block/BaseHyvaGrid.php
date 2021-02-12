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
            if (!$this->getData('grid_name')) {
                $msg = 'The name of the hyvä grid needs to be set on the block instance.';
                throw new \LogicException($msg);
            }

            $this->grid = $this->gridFactory->create(['gridName' => $this->_getData('grid_name')]);
        }
        return $this->grid;
    }

    public function getChildHtml($alias = "", $useCache = false): string
    {
        if ($blockDefinition = $this->children[$alias] ?? false) {
            /** @var Template $child */
            $child = $this->_layout->createBlock(Template::class);
            if(!isset($blockDefinition['template'])){
                throw new \InvalidArgumentException('Child template missing');
            }
            $child->setTemplate($blockDefinition['template']);

            if( isset($blockDefinition['view_model']) ){
                if (!$blockDefinition['view_model'] instanceof ArgumentInterface) {
                    throw new \InvalidArgumentException('ViewModel should be instance of ArgumentInterface');
                }
                $child->assign('view_model', $blockDefinition['view_model']);
            }

            $child->assign('grid', $this->getGrid());
            return $child->toHtml();
        }
        return parent::getChildHtml($alias, $useCache);
    }
}
