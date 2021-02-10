<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Hyva\Admin\Model\HyvaFormDefinition;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class HyvaFormDefinitionTest extends TestCase
{
    public function testReturnsTheName(): void
    {
        $arguments = ['formName' => 'test'];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);

        $this->assertInstanceOf(HyvaFormDefinition::class, $sut);
        $this->assertSame('test', $sut->getFormName());
    }

    public function testReturnsFieldDefinitions(): void
    {
        $stubFormConfigReader = new class implements HyvaFormConfigReaderInterface {
            public function getFormConfiguration(string $formName): array
            {
                return [
                    'fields' => [
                        'include' => [
                            'foo' => ['label' => 'Field One'],
                            'bar' => ['label' => 'Field Two'],
                        ],
                    ],
                ];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);
        $fieldDefinitions = $sut->getFieldDefinitions();
        $this->assertCount(2, $fieldDefinitions);
        $this->assertContainsOnlyInstancesOf(FormFieldDefinitionInterface::class, $fieldDefinitions);
    }
}
