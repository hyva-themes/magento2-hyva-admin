<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\Config;

use Hyva\Admin\Model\Config\HyvaGridDirs;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\TestCase;

class HyvaGridDirsTest extends TestCase
{
    private function createHyvaGridDirs(string $areaCode, array $activeModules): HyvaGridDirs
    {
        $stubAppState = $this->createMock(AppState::class);
        $stubAppState->method('getAreaCode')->willReturn($areaCode);

        $stubModuleList = $this->createMock(ModuleListInterface::class);
        $stubModuleList->method('getNames')->willReturn($activeModules);

        $stubComponentRegistrar = $this->createMock(ComponentRegistrarInterface::class);
        $stubComponentRegistrar->method('getPath')->willReturnCallback(
            fn(string $_, string $module) => __DIR__ . '/test-grid-dirs/' . $module
        );

        return new HyvaGridDirs($stubModuleList, $stubComponentRegistrar, $stubAppState);
    }

    public function testReturnsAdminAndBaseFiles(): void
    {
        $activeModules = ['Test_One', 'Test_Two', 'Test_Three'];
        $gridDirs = $this->createHyvaGridDirs('adminhtml', $activeModules);

        $expected = [
            __DIR__ . '/test-grid-dirs/Test_One/view/base/hyva-grid',
            __DIR__ . '/test-grid-dirs/Test_One/view/adminhtml/hyva-grid',
            __DIR__ . '/test-grid-dirs/Test_Two/view/adminhtml/hyva-grid',
        ];
        $this->assertSame($expected, $gridDirs->list());
    }

    public function testReturnsFrontendAndBaseFiles(): void
    {
        $activeModules = ['Test_One', 'Test_Two', 'Test_Three'];
        $gridDirs = $this->createHyvaGridDirs('frontend', $activeModules);

        $expected = [
            __DIR__ . '/test-grid-dirs/Test_One/view/base/hyva-grid',
            __DIR__ . '/test-grid-dirs/Test_Two/view/frontend/hyva-grid',
            __DIR__ . '/test-grid-dirs/Test_Three/view/frontend/hyva-grid',
        ];
        $this->assertSame($expected, $gridDirs->list());
    }
}
