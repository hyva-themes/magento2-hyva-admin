<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use Hyva\Admin\Model\FormStructure\MergeFormFieldDefinitionMaps;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_keys as keys;
use function array_merge as merge;

class MergeFormFieldDefinitionMapsTest extends TestCase
{
    public function testMergesEmptyInput(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [];
        $fieldsB = [];

        $this->assertSame([], $sut->merge($fieldsA, $fieldsB));
    }

    public function testMergesDistinctFormFields(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [
            'foo' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'foo']),
        ];
        $fieldsB = [
            'bar' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'bar']),
        ];

        $this->assertSame(merge($fieldsB, $fieldsA), $sut->merge($fieldsA, $fieldsB));
    }

    public function testMergesSameFormFields(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [
            'foo' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, [
                'name'      => 'foo',
                'groupId'   => 'groupA',
                'inputType' => 'text',
            ]),
        ];
        $fieldsB = [
            'foo' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, [
                'name'    => 'foo',
                'groupId' => 'groupB',
                'options' => [['label' => 'Test', 'value' => 1]],
            ]),
        ];

        $result = $sut->merge($fieldsA, $fieldsB);

        $this->assertCount(1, $result);
        $this->assertSame('foo', $result['foo']->getName());
        $this->assertSame('groupB', $result['foo']->getGroupId());
        $this->assertSame('text', $result['foo']->getInputType());
        $this->assertSame([['label' => 'Test', 'value' => 1]], $result['foo']->getOptions());
    }

    public function testMergesDIfferentAndSameFields(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [
            'onlyA' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'onlyA']),
            'both'  => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'both']),
        ];
        $fieldsB = [
            'both'  => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'both']),
            'onlyB' => ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, ['name' => 'onlyB']),
        ];

        $result = $sut->merge($fieldsA, $fieldsB);

        $this->assertCount(3, $result);
        $this->assertSame(['onlyB', 'both', 'onlyA'], keys($result));
        $this->assertSame('onlyB', $result['onlyB']->getName());
        $this->assertSame('both', $result['both']->getName());
        $this->assertSame('onlyA', $result['onlyA']->getName());
    }
}
