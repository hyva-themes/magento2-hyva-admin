<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\Config;

use Hyva\Admin\Model\Config\HyvaConfigDirs;
use Hyva\Admin\Model\Config\HyvaFormDirs;
use Hyva\Admin\Model\Config\HyvaGridDirs;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hyva\Admin\Model\Config\HyvaConfigDirs
 * @covers \Hyva\Admin\Model\Config\HyvaGridDirs
 * @covers \Hyva\Admin\Model\Config\HyvaFormDirs
 */
class HyvaConfigDirsTest extends TestCase
{
    private function createHyvaConfigDirsInstance(string $class, string $areaCode, array $activeModules): HyvaConfigDirs
    {
        $stubAppState = $this->createMock(AppState::class);
        $stubAppState->method('getAreaCode')->willReturn($areaCode);

        $stubModuleList = $this->createMock(ModuleListInterface::class);
        $stubModuleList->method('getNames')->willReturn($activeModules);

        $stubComponentRegistrar = $this->createMock(ComponentRegistrarInterface::class);
        $stubComponentRegistrar->method('getPath')->willReturnCallback(
            function (string $_, string $module): string {
                return __DIR__ . '/test-dirs/' . $module;
            }
        );

        return new $class($stubModuleList, $stubComponentRegistrar, $stubAppState);
    }

    /**
     * @dataProvider configDirProvider
     */
    public function testReturnsAdminAndBaseFiles(string $configDirsClass, string $dir): void
    {
        $activeModules = ['Test_One', 'Test_Two', 'Test_Three'];
        $gridDirs = $this->createHyvaConfigDirsInstance($configDirsClass, 'adminhtml', $activeModules);

        $expected = [
            __DIR__ . '/test-dirs/Test_One/view/base/' . $dir,
            __DIR__ . '/test-dirs/Test_One/view/adminhtml/' . $dir,
            __DIR__ . '/test-dirs/Test_Two/view/adminhtml/' . $dir,
        ];
        $this->assertSame($expected, $gridDirs->list());
    }

    /**
     * @dataProvider configDirProvider
     */
    public function testReturnsFrontendAndBaseFiles(string $configDirsClass, string $dir): void
    {
        $activeModules = ['Test_One', 'Test_Two', 'Test_Three'];
        $gridDirs = $this->createHyvaConfigDirsInstance($configDirsClass, 'frontend', $activeModules);

        $expected = [
            __DIR__ . '/test-dirs/Test_One/view/base/' . $dir,
            __DIR__ . '/test-dirs/Test_Two/view/frontend/' . $dir,
            __DIR__ . '/test-dirs/Test_Three/view/frontend/' . $dir,
        ];
        $this->assertSame($expected, $gridDirs->list());
    }

    public static function configDirProvider(): array
    {
        return [
            'grid config dirs' => [HyvaGridDirs::class, 'hyva-grid'],
            'form config dirs' => [HyvaFormDirs::class, 'hyva-form'],
        ];
    }
}
