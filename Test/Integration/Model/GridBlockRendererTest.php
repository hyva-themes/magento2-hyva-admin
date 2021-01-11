<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\GridBlockRenderer;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Hyva\Admin\Test\Integration\TestingGridDefinition;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GridBlockRendererTest extends TestCase
{
    /**
     * @after
     */
    public function cleanSharedGridDefinitionInstance(): void
    {
        $this->getTestObjectManager()->removeSharedInstance(HyvaGridDefinitionInterfaceFactory::class);
    }

    private function getTestObjectManager(): ObjectManager
    {
        /** @var ObjectManager $om */
        $om = ObjectManager::getInstance();
        return $om;
    }

    private function setStubGridSource(string $gridName, array $testGridData): void
    {
        $gridDefinition = [
            'source'     => [
                'arrayProvider' => TestingGridDataProvider::withArray($testGridData),
            ],
        ];

        $this->getTestObjectManager()->addSharedInstance(
            TestingGridDefinition::makeFactory($gridName, $gridDefinition),
            HyvaGridDefinitionInterfaceFactory::class
        );
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testRendersAdminGrid(): void
    {
        $gridName = 'test-grid';
        $this->setStubGridSource($gridName, [
            ['a' => 1, 'b' => 2],
            ['a' => 23, 'b' => 42],
        ]);

        /** @var GridBlockRenderer $renderer */
        $renderer = ObjectManager::getInstance()->create(GridBlockRenderer::class);

        $result = $renderer->renderGrid($gridName);
        $this->assertStringContainsString('class="hyva-admin-grid"', $result);
    }
}
