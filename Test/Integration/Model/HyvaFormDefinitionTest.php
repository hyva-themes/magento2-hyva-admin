<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Hyva\Admin\Model\Config\HyvaFormSectionsConfig;
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
        $sut              = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);
        $fieldDefinitions = $sut->getFieldDefinitions();
        $this->assertCount(2, $fieldDefinitions);
        $this->assertContainsOnlyInstancesOf(FormFieldDefinitionInterface::class, $fieldDefinitions);
    }

    public function testReturnsEmptyArrayIfNotSectionsAreDeclared(): void
    {
        $stubFormConfigReader = new class implements HyvaFormConfigReaderInterface {
            public function getFormConfiguration(string $formName): array
            {
                return ['sections' => []];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);
        $this->assertSame([], $sut->getGroupsFromSections());
    }

    public function testReturnsEmptyArrayIfEmptySectionsAreDeclared(): void
    {
        $stubFormConfigReader = new class implements HyvaFormConfigReaderInterface {
            public function getFormConfiguration(string $formName): array
            {
                return [
                    'sections' => [
                        ['id' => 'foo'],
                        ['id' => 'bar', 'groups' => []],
                    ],
                ];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);
        $this->assertSame([], $sut->getGroupsFromSections());
    }

    public function testReturnsFlatArrayOfGroups(): void
    {
        $group1         = ['id' => 'important-things', 'sortOrder' => '10'];
        $group2         = ['id' => 'details1', 'label' => 'Details', 'sortOrder' => '20'];
        $group3         = ['id' => 'whatever', 'sortOrder' => '10'];
        $group4         = ['id' => 'details2', 'sortOrder' => '20'];
        $sectionsConfig = [
            ['id' => 'foo', 'groups' => [$group1, $group2, []]],
            ['id' => 'bar', 'groups' => [$group3, $group4]],
        ];

        $stubFormConfigReader = new class($sectionsConfig) implements HyvaFormConfigReaderInterface {

            private $sectionsConfig;

            public function __construct(array $sectionsConfig)
            {
                $this->sectionsConfig = $sectionsConfig;
            }

            public function getFormConfiguration(string $formName): array
            {
                return [
                    'sections' => $this->sectionsConfig,
                ];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);

        $this->assertSame([$group1, $group2, $group3, $group4], $sut->getGroupsFromSections());
    }

    public function testThrowsExceptionWhenGroupIdsConflict(): void
    {
        $stubFormConfigReader = new class implements HyvaFormConfigReaderInterface {
            public function getFormConfiguration(string $formName): array
            {
                return [
                    'sections' => [
                        ['id' => 'foo', 'groups' => [['id' => 'a'], ['id' => 'b']]],
                        ['id' => 'bar', 'groups' => [['id' => 'c'], ['id' => 'a'], ['id' => 'd']]],
                        ['id' => 'baz', 'groups' => [['id' => 'd']]],
                    ],
                ];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The same section group ID(s) must not be used in multiple sections, found: ' .
            'foo/a, bar/a, bar/d, baz/d'
        );

        $sut->getGroupsFromSections();
    }
}
