<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Controller\Adminhtml\Ajax;

use Hyva\Admin\Block\Adminhtml\HyvaGrid;
use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Test\Integration\TestingGridConfig;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class PagingTest extends AbstractBackendController
{
    protected $resource = 'Magento_Backend::admin';

    protected $uri = 'backend/hyva-admin/ajax/paging/gridName/' . TestingGridConfig::GRID;

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

    private function dispatchPaging(string $gridName): array
    {
        $this->dispatch('backend/hyva-admin/ajax/paging/gridName/' . $gridName);

        return json_decode($this->getResponse()->getBody(), true);
    }

    public function testAclHasAccess()
    {
        $this->renderGridOnPage(TestingGridConfig::GRID);
        parent::testAclHasAccess();
    }

    public function testDeniesGridsThatWereNotRenderedInTheAdminSession(): void
    {
        $result = $this->dispatchPaging(TestingGridConfig::GRID);

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
        $this->assertSame('', $result['grid_html']);
        $this->assertSame('Access to this grid is denied. Please reload the page.', $result['message']);
    }

    public function testDeniesNonStringGridNames(): void
    {
        $this->renderGridOnPage(TestingGridConfig::GRID);
        $this->getRequest()->setQueryValue('gridName', [TestingGridConfig::GRID]);

        $this->dispatch('backend/hyva-admin/ajax/paging');

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
    }

    public function testTemplateDirectivesInTheCmsWysiwygEditorDoNotUnlockGrids(): void
    {
        // The CMS WYSIWYG directive controller processes directives in the admin area without area emulation.
        $directive = '{{block class="Hyva\\Admin\\Block\\Adminhtml\\HyvaGrid" grid_name="' . TestingGridConfig::GRID . '"}}';
        $encoded   = $this->_objectManager->get(UrlEncoderInterface::class)->encode($directive);
        try {
            $this->dispatch('backend/cms/wysiwyg/directive/___directive/' . $encoded);
        } catch (\Exception $exception) {
            // The directive result isn't a valid image path, which can throw after the directive was processed.
        }

        // Only one request can be dispatched per test, so the check the endpoints use is called directly.
        $gridAuthorization = $this->_objectManager->get(GridAuthorization::class);
        $this->assertFalse($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    public function testTemplateDirectivesInEmailPreviewsDoNotUnlockGrids(): void
    {
        $directive = '{{block class="Hyva\\Admin\\Block\\Adminhtml\\HyvaGrid" grid_name="' . TestingGridConfig::GRID . '"}}';
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'text'     => $directive,
            'type'     => 2,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ]);
        $this->dispatch('backend/admin/email_template/popup');

        // Only one request can be dispatched per test, so the check the endpoints use is called directly.
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringNotContainsString('grid-row-marker', (string) $this->getResponse()->getBody());
        $gridAuthorization = $this->_objectManager->get(GridAuthorization::class);
        $this->assertFalse($gridAuthorization->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    public function testRendersGridsThatWereRenderedInTheAdminSession(): void
    {
        $this->renderGridOnPage(TestingGridConfig::GRID);

        $result = $this->dispatchPaging(TestingGridConfig::GRID);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('grid-row-marker', $result['grid_html']);
        $this->assertNull($result['message']);
    }

    public function testRendersGridIfAclResourceIsAllowed(): void
    {
        $this->renderGridOnPage(TestingGridConfig::ACL_GRID);

        $result = $this->dispatchPaging(TestingGridConfig::ACL_GRID);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('grid-row-marker', $result['grid_html']);
    }

    public function testDeniesGridIfAclResourceIsDenied(): void
    {
        $this->renderGridOnPage(TestingGridConfig::ACL_GRID);
        $acl = $this->_objectManager->get(AclBuilder::class)->getAcl();
        $acl->deny($this->_auth->getUser()->getRoles(), TestingGridConfig::ACL_RESOURCE);

        $result = $this->dispatchPaging(TestingGridConfig::ACL_GRID);

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
        $this->assertSame('', $result['grid_html']);
    }
}
