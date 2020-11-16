<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Block;

use Hyva\Admin\Block\Adminhtml\HyvaGrid;
use Hyva\Admin\Block\Adminhtml\HyvaGrid as HyvaGridBlock;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridInterfaceFactory;
use Magento\Framework\View\Element\Template\Context as TemplateBlockContext;
use PHPUnit\Framework\TestCase;

class HyvaGridBlockTest extends TestCase
{
    public function testThrowsMeaningfullExceptionIfGridNameIsNotSet(): void
    {
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubContext     = $this->createMock(TemplateBlockContext::class);
        $sut             = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The name of the hyvä grid configuration needs ' .
            'to be configured in the block arguments.'
        );

        $sut->getGrid();
    }

    public function testReturnsGridInstance(): void
    {
        $stubGrid        = $this->createMock(HyvaGridInterface::class);
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubGridFactory->method('create')->willReturn($stubGrid);
        $arguments   = ['grid_name' => 'dummy-grid-name'];
        $stubContext = $this->createMock(TemplateBlockContext::class);

        $sut = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory, $arguments);

        $this->assertSame($stubGrid, $sut->getGrid());
    }

    public function testPassesGridNameToViewModelGrid(): void
    {
        $stubGrid        = $this->createMock(HyvaGridInterface::class);
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubGridFactory->expects($this->once())
                        ->method('create')
                        ->with(['gridName' => 'dummy-grid-name'])
                        ->willReturn($stubGrid);
        $arguments   = ['grid_name' => 'dummy-grid-name'];
        $stubContext = $this->createMock(TemplateBlockContext::class);

        $sut = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory, $arguments);

        $sut->getGrid();
    }
}
