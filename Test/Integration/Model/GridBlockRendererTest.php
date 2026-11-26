<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\GridBlockRenderer;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Hyva\Admin\Test\Integration\TestingGridDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\GridActionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridJsEvent;
use Hyva\Admin\ViewModel\HyvaGrid\GridJsEventFactory;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GridBlockRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->getTestObjectManager()->removeSharedInstance(HyvaGridDefinitionInterfaceFactory::class);
        $this->getTestObjectManager()->removeSharedInstance(GridJsEventFactory::class);
    }

    private function getTestObjectManager(): ObjectManager
    {
        /** @var ObjectManager $om */
        $om = ObjectManager::getInstance();
        return $om;
    }

    private function setStubGridSource(string $gridName, array $testGridData, array $actionsConfig = []): void
    {
        $gridDefinition = [
            'source'     => [
                'arrayProvider' => TestingGridDataProvider::withArray($testGridData),
            ],
            'actions'    => $actionsConfig,
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

    public static function rowActionIdValueProvider(): array
    {
        return [
            'plain value'        => ['MH01-XS-Black'],
            'double quotes'      => ['Shirt "XL"'],
            'single quotes'      => ["x' data-xss-marker='1"],
            'html entity'        => ['x&quot;y'],
            'html entity script' => ['&quot;+alert(1)+&quot;'],
            'closing tag'        => ['x\'></a><img src="x" data-xss-marker="1">'],
        ];
    }

    /**
     * @magentoAppArea adminhtml
     * @dataProvider rowActionIdValueProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rowActionIdValueProvider')]
    public function testEscapesRowValuesInActionEventAttributes(string $idValue): void
    {
        $html = $this->renderGridWithRowActionEvent($idValue);

        $this->assertActionLinkDispatchesValue($html, $idValue);
    }

    /**
     * Custom JS events might return params JSON that isn't encoded for HTML, so the template escapes it, too.
     *
     * @magentoAppArea adminhtml
     * @dataProvider rowActionIdValueProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rowActionIdValueProvider')]
    public function testEscapesUnencodedActionEventJsonInTheTemplate(string $idValue): void
    {
        $this->getTestObjectManager()->addSharedInstance(
            new class extends GridJsEventFactory {
                public function __construct()
                {
                }

                public function create(array $data = [])
                {
                    return new class($data['on'], $data['gridName'], $data['targetId']) extends GridJsEvent {
                        public function getParamsJson(GridActionInterface $action, RowInterface $row): string
                        {
                            return json_encode(['action' => $action->getId(), 'params' => $action->getParams($row)]);
                        }
                    };
                }
            },
            GridJsEventFactory::class
        );

        $html = $this->renderGridWithRowActionEvent($idValue);

        $this->assertActionLinkDispatchesValue($html, $idValue);
    }

    private function renderGridWithRowActionEvent(string $idValue): string
    {
        $gridName = 'test-grid';
        $this->setStubGridSource($gridName, [['sku' => $idValue]], [
            '@idColumn' => 'sku',
            'actions'   => [
                ['id' => 'edit', 'label' => 'Edit', 'url' => '*/*/edit', 'events' => ['click' => []]],
            ],
        ]);

        /** @var GridBlockRenderer $renderer */
        $renderer = ObjectManager::getInstance()->create(GridBlockRenderer::class);

        return $renderer->renderGrid($gridName);
    }

    private function assertActionLinkDispatchesValue(string $html, string $idValue): void
    {
        $links = $this->getActionLinks($html);
        $this->assertCount(1, $links);
        $link = $links[0];

        $attributeNames = [];
        foreach ($link->attributes as $attribute) {
            $attributeNames[] = $attribute->nodeName;
        }
        $this->assertSame(['class', 'href', 'x-on:click'], $attributeNames);
        $this->assertSame(0, $link->getElementsByTagName('*')->length, 'The action link has unexpected children');

        $expression = trim($link->getAttribute('x-on:click'));
        $this->assertSame(1, preg_match('/^dispatchRowEvent\(\$event, "([a-z-]+)", (.*)\)$/s', $expression, $matches));
        $this->assertSame(
            ['action' => 'edit', 'params' => ['sku' => $idValue]],
            json_decode($matches[2], true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return \DOMElement[]
     */
    private function getActionLinks(string $html): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $links = (new \DOMXPath($document))->query('//td[contains(@class, "actions")]/a');

        return iterator_to_array($links);
    }
}
