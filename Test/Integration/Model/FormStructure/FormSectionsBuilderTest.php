<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use Hyva\Admin\Model\FormStructure\FormSectionsBuilder;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_keys as keys;
use function array_merge as merge;

class FormSectionsBuilderTest extends TestCase
{
    /**
     * @param array[] $sectionConfig
     * @param FormGroupInterface[] $groups
     * @return FormSectionInterface[]
     */
    private function buildSections(array $sectionConfig, array $groups): array
    {
        /** @var FormSectionsBuilder $sut */
        $sut = ObjectManager::getInstance()->create(FormSectionsBuilder::class);
        return $sut->buildSections('test', $sectionConfig, $groups);
    }

    private function createGroup(array $data = []): FormGroupInterface
    {
        $default = [
            'fields'             => [],
            'sectionId'          => FormSectionInterface::DEFAULT_SECTION_ID,
            'sortOrder'          => 0,
            'isOnlyDefaultGroup' => true,
        ];
        return $group = ObjectManager::getInstance()->create(FormGroupInterface::class, merge($default, $data));
    }

    public function testEmptyGroupsResultsInEmptySections(): void
    {
        $sectionConfig = [];
        $groups        = [];
        $sections      = $this->buildSections($sectionConfig, $groups);

        $this->assertSame([], $sections);
    }

    public function testEmptySectionsConfigButGroupsLeadsToSingleAutomaticSection(): void
    {
        $sectionConfig = [];
        $groups        = [
            $this->createGroup(['id' => 'foo']),
            $this->createGroup(['id' => 'bar']),
        ];
        $sections      = $this->buildSections($sectionConfig, $groups);

        $defaultSectionId = FormSectionInterface::DEFAULT_SECTION_ID;
        $this->assertCount(1, $sections);
        $this->assertSame($defaultSectionId, $sections[$defaultSectionId]->getId());
        $this->assertCount(2, $sections[$defaultSectionId]->getGroups());
    }

    public function testCreatesOnlySectionsThatHaveGroups(): void
    {
        $sectionConfig = [
            'section1' => ['id' => 'section1', 'groups' => []],
            'section2' => ['id' => 'section2'],
        ];
        $groups        = [
            $this->createGroup(['id' => 'foo', 'sectionId' => 'section2']),
            $this->createGroup(['id' => 'bar', 'sectionId' => 'section2']),
        ];

        $sections = $this->buildSections($sectionConfig, $groups);

        $this->assertCount(1, $sections);
        $this->assertSame(['section2'], keys($sections));
    }

    public function testKeepsOrderStableIfNoSortOrderConfig(): void
    {
        $sectionConfig = [
            'section1' => ['id' => 'section1'],
            'section2' => ['id' => 'section2'],
            'section3' => ['id' => 'section3'],
        ];
        $groups        = [
            $this->createGroup(['id' => 'foo', 'sectionId' => 'section2']),
            $this->createGroup(['id' => 'bar', 'sectionId' => 'section1']),
            $this->createGroup(['id' => 'baz', 'sectionId' => 'section3']),
        ];

        $sections = $this->buildSections($sectionConfig, $groups);

        $this->assertSame(['section1', 'section2', 'section3'], keys($sections));
    }

    public function testSortsSectionsWithSortOrderFirst(): void
    {
        $sectionConfig = [
            'section1' => ['id' => 'section1'],
            'section2' => ['id' => 'section2'],
            'section3' => ['id' => 'section3', 'sortOrder' => 2],
            'section4' => ['id' => 'section4', 'sortOrder' => 1],
        ];
        $groups        = [
            $this->createGroup(['id' => 'foo', 'sectionId' => 'section2']),
            $this->createGroup(['id' => 'bar', 'sectionId' => 'section1']),
            $this->createGroup(['id' => 'baz', 'sectionId' => 'section3']),
            $this->createGroup(['id' => 'qux', 'sectionId' => 'section4']),
        ];

        $sections = $this->buildSections($sectionConfig, $groups);

        $this->assertSame(['section4', 'section3', 'section1', 'section2'], keys($sections));
    }
}
