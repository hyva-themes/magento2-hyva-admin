<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use Hyva\Admin\Model\FormStructure\FormGroupsBuilder;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class FormGroupsBuilderTest extends TestCase
{
    public function testEmptyFieldsReturnsEmptyGroups(): void
    {
        /** @var FormGroupsBuilder $sut */
        $sut = ObjectManager::getInstance()->create(FormGroupsBuilder::class);

        $fields             = [];
        $groupIdToConfigMap = ['foo' => ['id' => 'foo']];

        $this->assertSame([], $sut->buildGroups($fields, $groupIdToConfigMap));
    }

    public function testFieldsWithEmptyConfig(): void
    {
        $this->markTestIncomplete();
    }

    public function testFieldsAndConfig(): void
    {
        $this->markTestIncomplete();
    }

    public function testAssociatesFieldsToTheCorrectGroups(): void
    {
        $this->markTestIncomplete();
    }
}
