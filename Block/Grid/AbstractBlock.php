<?php
/**
 * AbstractBlock
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

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