<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\Config;

use Hyva\Admin\Model\Config\GridConfigReader;
use Hyva\Admin\Model\Config\HyvaGridConfigReaderInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class GridConfigReaderTest extends TestCase
{
    public function testIsKnownToObjectManager(): void
    {
        $reader = ObjectManager::getInstance()->create(HyvaGridConfigReaderInterface::class);
        $this->assertInstanceOf(GridConfigReader::class, $reader);
    }

    public function testLoadsSingleFile(): void
    {
        $stubConfigFileList = $this->createMock(\Hyva\Admin\Model\Config\GridDefinitionConfigFiles::class);
        $stubConfigFileList->method('getConfigDefinitionFiles')->willReturn([
            __DIR__ . '/test-grid-files/test-grid-a.xml',
        ]);
        $arguments = ['gridDefinitionConfigFiles' => $stubConfigFileList];

        /** @var GridConfigReader $reader */
        $reader = ObjectManager::getInstance()->create(GridConfigReader::class, $arguments);
        $result = $reader->getGridConfiguration('test');
        $this->assertSame('\Foo', $result['source']['arrayProvider']);
    }

    public function testMergesFiles(): void
    {
        $stubConfigFileList = $this->createMock(\Hyva\Admin\Model\Config\GridDefinitionConfigFiles::class);
        $stubConfigFileList->method('getConfigDefinitionFiles')->willReturn([
            __DIR__ . '/test-grid-files/test-grid-a.xml',
            __DIR__ . '/test-grid-files/test-grid-b.xml',
        ]);
        $arguments = ['gridDefinitionConfigFiles' => $stubConfigFileList];

        /** @var GridConfigReader $reader */
        $reader = ObjectManager::getInstance()->create(GridConfigReader::class, $arguments);
        $result = $reader->getGridConfiguration('test');

        $this->assertSame('\Bar', $result['source']['arrayProvider']);
        $this->assertSame([
            ['key' => 'aaa'],
            ['key' => 'bbb', 'label' => 'file B label'],
            ['key' => 'yyy'],
        ], $result['columns']['include']);
    }
}
