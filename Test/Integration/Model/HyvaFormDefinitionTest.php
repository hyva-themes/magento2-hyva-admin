<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Hyva\Admin\Model\HyvaFormDefinition;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_merge as merge;

/**
 * @covers \Hyva\Admin\Model\HyvaFormDefinition
 */
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
        $stubFormConfigReader = new class() implements HyvaFormConfigReaderInterface {
            public function getFormConfiguration(string $formName): array
            {
                return [
                    'fields' => [
                        'include' => [
                            ['label' => 'Field One', 'name' => 'foo'],
                            ['label' => 'Field Two', 'name' => 'bar'],
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

    /**
     * @magentoAppArea adminhtml
     * @dataProvider joinColumnsTestDataProvider
     */
    public function testCastsJoinFieldPropertyToBool(array $columnConfig, bool $expectsJoinedColumns): void
    {
        $stubFormConfigReader = new class($columnConfig) implements HyvaFormConfigReaderInterface {
            private $columnConfig;

            public function __construct(array $columnConfig)
            {
                $this->columnConfig = $columnConfig;
            }

            public function getFormConfiguration(string $formName): array
            {
                return [
                    'fields' => [
                        'include' => [$this->columnConfig],
                    ],
                ];
            }
        };

        $arguments = ['formName' => 'test', 'formConfigReader' => $stubFormConfigReader];
        /** @var HyvaFormDefinition $sut */
        $sut              = ObjectManager::getInstance()->create(HyvaFormDefinitionInterface::class, $arguments);
        $fieldDefinitions = $sut->getFieldDefinitions();
        if ($expectsJoinedColumns) {
            $this->assertStringContainsString('col-span-2', $fieldDefinitions['foo']->getHtml());
        } else {
            $this->assertStringNotContainsString('col-span-2', $fieldDefinitions['foo']->getHtml());
        }
    }

    public function joinColumnsTestDataProvider(): array
    {
        return [
            'explicit-true'  => [['name' => 'foo', 'joinColumns' => 'true'], true],
            'explicit-false' => [['name' => 'foo', 'joinColumns' => 'false'], false],
            'implicit-false' => [['name' => 'foo'], false],
        ];
    }

    public function testReturnsEmptyArrayIfNotSectionsAreDeclared(): void
    {
        $stubFormConfigReader = new class() implements HyvaFormConfigReaderInterface {
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
        $stubFormConfigReader = new class() implements HyvaFormConfigReaderInterface {
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

    public function testReturnsFlatMapOfGroups(): void
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

        $this->assertSame([
            'important-things' => merge($group1, ['sectionId' => 'foo']),
            'details1'         => merge($group2, ['sectionId' => 'foo']),
            'whatever'         => merge($group3, ['sectionId' => 'bar']),
            'details2'         => merge($group4, ['sectionId' => 'bar']),
        ], $sut->getGroupsFromSections());
    }

    public function testThrowsExceptionWhenGroupIdsConflict(): void
    {
        $stubFormConfigReader = new class() implements HyvaFormConfigReaderInterface {
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

    public function testReturnsMapOfSections()
    {
        $group1         = ['id' => 'important-things', 'sortOrder' => '10'];
        $group2         = ['id' => 'details2', 'sortOrder' => '20'];
        $sectionsConfig = [
            ['id' => 'foo', 'groups' => [$group1]],
            ['id' => 'bar', 'groups' => [$group2]],
            ['id' => 'baz', 'groups' => []],
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

        $this->assertSame([
            'foo' => ['id' => 'foo', 'groups' => [$group1]],
            'bar' => ['id' => 'bar', 'groups' => [$group2]],
            'baz' => ['id' => 'baz', 'groups' => []],
        ], $sut->getSectionsConfig());
    }
}
