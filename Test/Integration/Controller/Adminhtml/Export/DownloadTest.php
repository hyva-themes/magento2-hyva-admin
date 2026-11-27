<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Controller\Adminhtml\Export;

use Hyva\Admin\Block\Adminhtml\HyvaGrid;
use Hyva\Admin\Test\Integration\TestingGridConfig;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class DownloadTest extends AbstractBackendController
{
    protected $resource = 'Magento_Backend::admin';

    protected $uri = 'backend/hyva-admin/export/download/gridName/' . TestingGridConfig::GRID . '/exportType/csv';

    protected function setUp(): void
    {
        parent::setUp();
        TestingGridConfig::install();
        TestingGridDataProvider::withArray([['id' => 1, 'name' => 'grid-row-marker']]);
        $this->_objectManager->get(BackendSession::class)->clearStorage();
    }

    protected function tearDown(): void
    {
        $this->_objectManager->get(BackendSession::class)->clearStorage();
        TestingGridConfig::uninstall();
        parent::tearDown();
    }

    private function renderGridOnPage(string $gridName): void
    {
        $layout = $this->_objectManager->get(LayoutInterface::class);
        $layout->createBlock(HyvaGrid::class, '', ['data' => ['grid_name' => $gridName]])->toHtml();

        // Building URLs while rendering turns the secret keys back on, which the test case turned off.
        $this->_objectManager->get(BackendUrlInterface::class)->turnOffSecretKey();
    }

    private function dispatchExport(string $gridName): void
    {
        $this->dispatch('backend/hyva-admin/export/download/gridName/' . $gridName . '/exportType/csv');
    }

    public function testAclHasAccess()
    {
        $this->renderGridOnPage(TestingGridConfig::GRID);
        parent::testAclHasAccess();
    }

    public function testDeniesGridsThatWereNotRenderedInTheAdminSession(): void
    {
        $this->dispatchExport(TestingGridConfig::GRID);

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('grid-row-marker', (string) $this->getResponse()->getBody());
    }

    public function testExportsGridsThatWereRenderedInTheAdminSession(): void
    {
        $this->renderGridOnPage(TestingGridConfig::GRID);
        // The backend file factory redirects to the dashboard on the first request after the login.
        $this->_auth->getAuthStorage()->setIsFirstPageAfterLogin(false);
        // The file response sends the export file contents when the test request is dispatched.
        $this->expectOutputRegex('/grid-row-marker/');

        $this->dispatchExport(TestingGridConfig::GRID);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    public function testDeniesGridIfAclResourceIsDenied(): void
    {
        $this->renderGridOnPage(TestingGridConfig::ACL_GRID);
        $acl = $this->_objectManager->get(AclBuilder::class)->getAcl();
        $acl->deny($this->_auth->getUser()->getRoles(), TestingGridConfig::ACL_RESOURCE);

        $this->dispatchExport(TestingGridConfig::ACL_GRID);

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
    }
}
