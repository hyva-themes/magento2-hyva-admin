<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Block\Adminhtml;

use Hyva\Admin\Block\Adminhtml\HyvaGrid;
use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Test\Integration\TestingGridConfig;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Cms\Model\Template\Filter as CmsTemplateFilter;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class HyvaGridTest extends TestCase
{
    protected function setUp(): void
    {
        TestingGridConfig::install();
        TestingGridDataProvider::withArray([['id' => 1, 'name' => 'grid-row-marker']]);
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
    }

    protected function tearDown(): void
    {
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
        TestingGridConfig::uninstall();
    }

    private function createGridAuthorization(bool $isAclResourceAllowed): GridAuthorization
    {
        $stubAuthorization = $this->createStub(AuthorizationInterface::class);
        $stubAuthorization->method('isAllowed')->willReturn($isAclResourceAllowed);

        return ObjectManager::getInstance()->create(GridAuthorization::class, [
            'authorization' => $stubAuthorization,
        ]);
    }

    private function renderGrid(string $gridName, GridAuthorization $gridAuthorization): string
    {
        /** @var LayoutInterface $layout */
        $layout = ObjectManager::getInstance()->get(LayoutInterface::class);
        $block  = $layout->createBlock(HyvaGrid::class, '', [
            'data'              => ['grid_name' => $gridName],
            'gridAuthorization' => $gridAuthorization,
        ]);

        return $block->toHtml();
    }

    public function testRegistersRenderedGridForTheEndpoints(): void
    {
        $gridAuthorization = $this->createGridAuthorization(true);

        $html = $this->renderGrid(TestingGridConfig::GRID, $gridAuthorization);

        $this->assertStringContainsString('grid-row-marker', $html);
        $this->assertTrue($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    public function testRendersGridIfAclResourceIsAllowed(): void
    {
        $gridAuthorization = $this->createGridAuthorization(true);

        $html = $this->renderGrid(TestingGridConfig::ACL_GRID, $gridAuthorization);

        $this->assertStringContainsString('grid-row-marker', $html);
        $this->assertTrue($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::ACL_GRID));
    }

    public function testDoesNotRenderOrRecordGridsThroughTemplateDirectives(): void
    {
        $templateFilter = ObjectManager::getInstance()->create(CmsTemplateFilter::class);

        $html = $templateFilter->filter(
            '{{block class="Hyva\\Admin\\Block\\Adminhtml\\HyvaGrid" grid_name="' . TestingGridConfig::GRID . '"}}'
        );

        $this->assertStringNotContainsString('grid-row-marker', $html);
        $this->assertFalse($this->createGridAuthorization(true)->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    public function testDoesNotRenderOrRecordGridsInEmulatedAreas(): void
    {
        $gridAuthorization = $this->createGridAuthorization(true);

        $html = ObjectManager::getInstance()->get(AppState::class)->emulateAreaCode(
            Area::AREA_ADMINHTML,
            function () use ($gridAuthorization): string {
                return $this->renderGrid(TestingGridConfig::GRID, $gridAuthorization);
            }
        );

        $this->assertSame('', $html);
        $this->assertFalse($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    /**
     * @magentoAppArea frontend
     */
    public function testRendersGridsOnTheStorefrontWithoutRecordingThem(): void
    {
        $gridAuthorization = $this->createMock(GridAuthorization::class);
        $gridAuthorization->expects($this->never())->method('isAllowed');
        $gridAuthorization->expects($this->never())->method('registerRenderedGrid');

        /** @var LayoutInterface $layout */
        $layout = ObjectManager::getInstance()->get(LayoutInterface::class);
        $block  = $layout->createBlock(HyvaGrid::class, '', [
            // The grid template only exists in the admin, so a storefront template is used instead
            'gridTemplate'      => 'Magento_Theme::html/copyright.phtml',
            'data'              => ['grid_name' => TestingGridConfig::GRID],
            'gridAuthorization' => $gridAuthorization,
        ]);

        $this->assertStringContainsString('class="copyright"', $block->toHtml());
    }

    public function testDoesNotRenderGridIfAclResourceIsDenied(): void
    {
        $gridAuthorization = $this->createGridAuthorization(false);

        $html = $this->renderGrid(TestingGridConfig::ACL_GRID, $gridAuthorization);

        $this->assertSame('', $html);
        $this->assertFalse($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::ACL_GRID));
        $this->assertFalse($this->createGridAuthorization(true)->isAllowedForEndpoint(TestingGridConfig::ACL_GRID));
    }
}
