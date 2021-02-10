<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\Config;

use Hyva\Admin\Model\Config\FormConfigReader;
use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class FormConfigReaderTest extends TestCase
{
    public function testIsKnownToObjectManager(): void
    {
        $reader = ObjectManager::getInstance()->create(HyvaFormConfigReaderInterface::class);
        $this->assertInstanceOf(FormConfigReader::class, $reader);
    }

    public function testLoadsSingleFile(): void
    {
        $stubConfigFileList = $this->createMock(\Hyva\Admin\Model\Config\FormDefinitionConfigFiles::class);
        $stubConfigFileList->method('getConfigDefinitionFiles')->willReturn([
            __DIR__ . '/test-form-files/test-form-a.xml',
        ]);
        $arguments = ['formDefinitionConfigFiles' => $stubConfigFileList];

        /** @var FormConfigReader $reader */
        $reader = ObjectManager::getInstance()->create(FormConfigReader::class, $arguments);
        $result = $reader->getFormConfiguration('test');
        $this->assertSame('\Some\Repo::getById', $result['load']['method']);
    }

    public function testMergesFiles(): void
    {
        $stubConfigFileList = $this->createMock(\Hyva\Admin\Model\Config\FormDefinitionConfigFiles::class);
        $stubConfigFileList->method('getConfigDefinitionFiles')->willReturn([
            __DIR__ . '/test-form-files/test-form-a.xml',
            __DIR__ . '/test-form-files/test-form-b.xml',
        ]);
        $arguments = ['formDefinitionConfigFiles' => $stubConfigFileList];

        /** @var FormConfigReader $reader */
        $reader = ObjectManager::getInstance()->create(FormConfigReader::class, $arguments);
        $result = $reader->getFormConfiguration('test');

        $this->assertSame('\Some\Repo::getById', $result['load']['method']);
        $this->assertSame('false', $result['save']['bindArguments']['foo']['formData']);
        $this->assertSame('true', $result['save']['bindArguments']['bar']['formData']);
        $this->assertSame('true', $result['save']['bindArguments']['bar']['formData']);
        $this->assertSame(['name' => 'aaa'], $result['fields']['include'][0]);
        $this->assertSame(
            ['name' => 'bbb', 'group' => 'important-things', 'template' => 'Some_Module::bbb.phtml'],
            $result['fields']['include'][1]
        );
        $this->assertSame(['ccc', 'ddd'], $result['fields']['exclude']);
    }
}
