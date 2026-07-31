<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use function array_keys as keys;
use function array_merge as merge;

use Hyva\Admin\Model\FormStructure\FormGroupsBuilder;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class FormGroupsBuilderTest extends TestCase
{
    private function createField(array $fieldData): FormFieldDefinitionInterface
    {
        $defaults  = ['formName' => 'test'];
        return ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, merge($defaults, $fieldData));
    }

    /**
     * @return FormGroupInterface[]
     */
    private function buildGroups(array $fields, array $groupIdToConfigMap): array
    {
        /** @var FormGroupsBuilder $sut */
        $sut = ObjectManager::getInstance()->create(FormGroupsBuilder::class);
        return $sut->buildGroups($fields, $groupIdToConfigMap);
    }

    public function testEmptyFieldsReturnsEmptyGroups(): void
    {
        $fields             = [];
        $groupIdToConfigMap = ['foo' => ['id' => 'foo']];

        $this->assertSame([], $this->buildGroups($fields, $groupIdToConfigMap));
    }

    public function testFieldsWithEmptyConfig(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo', 'groupId' => 'group1']),
            $this->createField(['name' => 'bar', 'groupId' => 'group1']),
            $this->createField(['name' => 'baz', 'groupId' => 'group2']),
        ];
        $groupIdToConfigMap = [];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups['group1']->getFields());
        $this->assertCount(1, $groups['group2']->getFields());
    }

    public function testFieldsAndConfig(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo', 'groupId' => 'group1']),
            $this->createField(['name' => 'bar', 'groupId' => 'group1']),
            $this->createField(['name' => 'baz', 'groupId' => 'group2']),
        ];
        $groupIdToConfigMap = [
            'group1' => ['id' => 'group1', 'label' => 'Expected'],
            'group3' => ['id' => 'group3']
        ];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups['group1']->getFields());
        $this->assertCount(1, $groups['group2']->getFields());
        $this->assertSame('Expected', $groups['group1']->getLabel());
    }

    public function testSortsGroupsWithSortOrder(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo', 'groupId' => 'group1']),
            $this->createField(['name' => 'bar', 'groupId' => 'group2']),
            $this->createField(['name' => 'baz', 'groupId' => 'group3']),
            $this->createField(['name' => 'qux', 'groupId' => 'group4']),
        ];
        $groupIdToConfigMap = [
            'group1' => ['id' => 'group1', 'sortOrder' => 2],
            'group2' => ['id' => 'group2', 'sortOrder' => 5],
            'group3' => ['id' => 'group3', 'sortOrder' => 1],
            'group4' => ['id' => 'group4', 'sortOrder' => -1],
        ];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertSame(['group4', 'group3', 'group1', 'group2'], keys($groups));
    }

    public function testKeepsOrderFromConfigBeforeOrderFromGroupsWithNoSortOrder(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo', 'groupId' => 'xgroup1']),
            $this->createField(['name' => 'bar', 'groupId' => 'only-on-field']),
            $this->createField(['name' => 'baz', 'groupId' => 'group3']),
        ];
        $groupIdToConfigMap = [
            'xgroup1' => ['id' => 'xgroup1', 'label' => 'Expected'],
            'group3' => ['id' => 'group3']
        ];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertSame(['xgroup1', 'group3', 'only-on-field'], keys($groups));
    }

    public function testSortsGroupsWithNoSortOrderAfterGroupsWithSortOrder(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo', 'groupId' => 'group1']),
            $this->createField(['name' => 'bar', 'groupId' => 'group2']),
            $this->createField(['name' => 'baz', 'groupId' => 'group3']),
            $this->createField(['name' => 'qux', 'groupId' => 'group4']),
        ];
        $groupIdToConfigMap = [
            'group1' => ['id' => 'group1'],
            'group2' => ['id' => 'group2'],
            'group3' => ['id' => 'group3', 'sortOrder' => 50],
            'group4' => ['id' => 'group4', 'sortOrder' => 10],
        ];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertSame(['group4', 'group3', 'group1', 'group2'], keys($groups));
    }

    public function testSetsOnlyDefaultGroupFlagToTrueIfNoGroupConfig(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo']),
            $this->createField(['name' => 'bar']),
        ];
        $groupIdToConfigMap = [];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertArrayHasKey(FormGroupInterface::DEFAULT_GROUP_ID, $groups);
        $this->assertTrue($groups[FormGroupInterface::DEFAULT_GROUP_ID]->isOnlyDefaultGroup());
    }

    public function testSetsOnlyDefaultGroupFlagToFalseIfGroupConfig(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo']),
            $this->createField(['name' => 'bar']),
        ];
        $groupIdToConfigMap = [
            FormGroupInterface::DEFAULT_GROUP_ID => ['id' => FormGroupInterface::DEFAULT_GROUP_ID],
        ];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertArrayHasKey(FormGroupInterface::DEFAULT_GROUP_ID, $groups);
        $this->assertFalse($groups[FormGroupInterface::DEFAULT_GROUP_ID]->isOnlyDefaultGroup());
    }

    public function testSetsOnlyDefaultGroupFlagToFalseIfMultipleGroups(): void
    {
        $fields    = [
            $this->createField(['name' => 'foo']),
            $this->createField(['name' => 'bar', 'groupId' => 'foo']),
        ];
        $groupIdToConfigMap = [];

        $groups = $this->buildGroups($fields, $groupIdToConfigMap);
        $this->assertArrayHasKey(FormGroupInterface::DEFAULT_GROUP_ID, $groups);
        $this->assertArrayHasKey('foo', $groups);
        $this->assertFalse($groups[FormGroupInterface::DEFAULT_GROUP_ID]->isOnlyDefaultGroup());
        $this->assertFalse($groups['foo']->isOnlyDefaultGroup());
    }
}
