<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\View\Element\Template;

abstract class BaseHyvaGrid extends Template
{
    /**
     * @var HyvaGridInterfaceFactory
     */
    private $gridFactory;

    /**
     * @var HyvaGridInterface
     */
    private $grid;

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

            $this->grid = $this->gridFactory->create(['gridName' => $gridName]);
        }
        return $this->grid;
    }
}
