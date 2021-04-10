<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridExport;

use Hyva\Admin\Model\GridExport\GridExportTypeLocator;
use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\Model\GridExport\Type\Csv;
use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GridExportTypeLocatorTest extends TestCase
{
    public function testThrowsExceptionIfExportIsNotConfiguredOnGrid(): void
    {
        $stubGrid = $this->createMock(HyvaGridExportInterface::class);
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Export type "foo" not configured for Hyvä grid "stub-grid"');

        $sut->getExportType($stubGrid, 'foo');
    }

    public function testThrowsExceptionIfGridTypeIsUnknownAndNoClassIsSet(): void
    {
        $stubGrid = $this->createMock(HyvaGridExportInterface::class);
        $stubGrid->method('getExport')->willReturn($this->createMock(GridExportInterface::class));
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Hyva_Admin Grid Export type "foo" is unknown');

        $sut->getExportType($stubGrid, 'foo');
    }

    public function testReturnsCustomExportClassIfConfigured()
    {
        $stubExport = $this->createMock(GridExportInterface::class);
        $stubExport->method('getClass')->willReturn(Csv::class);
        $stubGrid = $this->createMock(HyvaGridExportInterface::class);
        $stubGrid->method('getExport')->willReturn($stubExport);
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $exportType = $sut->getExportType($stubGrid, 'foo');
        $this->assertInstanceOf(Csv::class, $exportType);
    }
}
