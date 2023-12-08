<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Block\Adminhtml\HyvaGrid;
use Magento\Framework\View\LayoutInterface;

class GridBlockRenderer
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $gridClass;

    public function __construct(LayoutInterface $layout, string $gridClass = HyvaGrid::class)
    {
        $this->layout    = $layout;
        $this->gridClass = $gridClass;
    }

    public function renderGrid(string $gridName): string
    {
        $this->layout->getUpdate()->load('formkey');
        $this->layout->generateXml();
        $this->layout->generateElements();
        $arguments = ['data' => ['grid_name' => $gridName]];
        $block     = $this->layout->createBlock($this->gridClass, 'hyva_grid_block', $arguments);

        return $block->toHtml();
    }
}
