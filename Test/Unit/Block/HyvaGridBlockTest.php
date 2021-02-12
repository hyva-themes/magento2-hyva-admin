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
    public function testThrowsMeaningfulExceptionIfGridNameIsNotSet(): void
    {
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubContext     = $this->createMock(TemplateBlockContext::class);
        $sut             = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The name of the hyvä grid needs ' .
            'to be set on the block instance.'
        );

        $sut->getGrid();
    }

    public function testUsesGivenGridNameArgument(): void
    {
        $testGridName    = 'qux';
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubGridFactory->expects($this->once())
                        ->method('create')
                        ->with(['gridName' => $testGridName])
                        ->willReturn($this->createMock(HyvaGridInterface::class));

        $stubContext = $this->createMock(TemplateBlockContext::class);
        $blockData   = ['grid_name' => $testGridName];
        $sut         = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory, $blockData);
        $sut->setNameInLayout('bar');

        $sut->getGrid();
    }

    public function testFallsBackToBlockNameIfNoGridNameIsSet(): void
    {
        $testGridName    = 'foo';
        $stubGridFactory = $this->createMock(HyvaGridInterfaceFactory::class);
        $stubGridFactory->expects($this->once())
                        ->method('create')
                        ->with(['gridName' => $testGridName])
                        ->willReturn($this->createMock(HyvaGridInterface::class));

        $stubContext = $this->createMock(TemplateBlockContext::class);
        $sut         = new HyvaGridBlock($stubContext, 'dummy-template.phtml', $stubGridFactory);
        $sut->setNameInLayout($testGridName);

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
