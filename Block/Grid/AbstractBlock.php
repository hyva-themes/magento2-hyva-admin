<?php

namespace Hyva\Admin\Block\Grid;

use Hyva\Admin\Block\BaseHyvaGrid;
use Magento\Framework\View\Element\Template;

abstract class AbstractBlock extends Template
{
    /**
     * @return BaseHyvaGrid
     */
    public function getParent() :BaseHyvaGrid
    {
        return $this->getData('parent');
    }
}