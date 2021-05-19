<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use Hyva\Admin\Model\FormStructure\MergeFormFieldDefinitionMaps;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Magento\Config\Model\Config\Source\Yesno as YesnoSourceModel;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_keys as keys;
use function array_merge as merge;

class MergeFormFieldDefinitionMapsTest extends TestCase
{
    private function createField(array $fieldData): FormFieldDefinitionInterface
    {
        $defaults = ['formName' => 'test'];
        return ObjectManager::getInstance()->create(FormFieldDefinitionInterface::class, merge($defaults, $fieldData));
    }

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

        $fieldsA   = [
            'foo' => $this->createField(['name' => 'foo']),
        ];
        $fieldsB   = [
            'bar' => $this->createField(['name' => 'bar']),
        ];

        $this->assertSame(merge($fieldsB, $fieldsA), $sut->merge($fieldsA, $fieldsB));
    }

    public function testMergesSameFormFields(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [
            'foo' => $this->createField([
                'name'      => 'foo',
                'groupId'   => 'groupA',
                'inputType' => 'text',
            ]),
        ];
        $fieldsB = [
            'foo' => $this->createField([
                'name'    => 'foo',
                'groupId' => 'groupB',
                'source' => SourceModelStub::class,
            ]),
        ];

        $result = $sut->merge($fieldsA, $fieldsB);

        $this->assertCount(1, $result);
        $this->assertSame('foo', $result['foo']->getName());
        $this->assertSame('groupB', $result['foo']->getGroupId());
        $this->assertSame('text', $result['foo']->getInputType());
        $expectedOptions = (new SourceModelStub())->toOptionArray();
        $this->assertSame($expectedOptions, $result['foo']->getOptions());
    }

    public function testMergesDifferentAndSameFields(): void
    {
        /** @var MergeFormFieldDefinitionMaps $sut */
        $sut = ObjectManager::getInstance()->create(MergeFormFieldDefinitionMaps::class);

        $fieldsA = [
            'onlyA' => $this->createField(['name' => 'onlyA']),
            'both'  => $this->createField(['name' => 'both']),
        ];
        $fieldsB = [
            'both'  => $this->createField(['name' => 'both']),
            'onlyB' => $this->createField(['name' => 'onlyB']),
        ];

        $result = $sut->merge($fieldsA, $fieldsB);

        $this->assertCount(3, $result);
        $this->assertSame(['both', 'onlyB', 'onlyA'], keys($result));
        $this->assertSame('onlyB', $result['onlyB']->getName());
        $this->assertSame('both', $result['both']->getName());
        $this->assertSame('onlyA', $result['onlyA']->getName());
    }
}
