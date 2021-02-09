<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;

abstract class BaseHyvaGrid extends Template
{
    /**
     * @var \Hyva\Admin\ViewModel\HyvaGridInterfaceFactory
     */
    private $gridFactory;

    private $children;

    private $grid;

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
        if (!$this->grid) {
            if (!$this->getData('grid_name')) {
                $msg = 'The name of the hyvä grid needs to be set on the block instance.';
                throw new \LogicException($msg);
            }

            $this->grid =  $this->gridFactory->create(['gridName' => $this->_getData('grid_name')]);
        }
        return $this->grid;
    }

    public function getChildDataHtml($identifier): string
    {
        if ($block = $this->children[$identifier] ?? false) {
            if( ! $block instanceof BlockInterface ){
                throw new \InvalidArgumentException('Child should be instance of BlockInterface');
            }
            $block->setParent($this);
            return $block->toHtml();
        }
        return "";
    }
}
