<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Controller\Adminhtml\Ajax;

use Hyva\Admin\Controller\Adminhtml\Ajax\Paging;
use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Test\Integration\TestingGridConfig;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class PagingErrorMessageTest extends TestCase
{
    private const GRID_WITHOUT_CONFIG = 'grid-without-config';

    protected function setUp(): void
    {
        TestingGridConfig::install();
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
        // A recorded grid without configuration fails to render
        ObjectManager::getInstance()->get(GridAuthorization::class)->registerRenderedGrid(self::GRID_WITHOUT_CONFIG);
    }

    protected function tearDown(): void
    {
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
        TestingGridConfig::uninstall();
    }

    private function executePaging(string $mode): array
    {
        $stubAppState = $this->createStub(AppState::class);
        $stubAppState->method('getMode')->willReturn($mode);

        $request = ObjectManager::getInstance()->create(HttpRequest::class);
        $request->setParam('gridName', self::GRID_WITHOUT_CONFIG);

        $controller = ObjectManager::getInstance()->create(Paging::class, [
            'request'  => $request,
            'appState' => $stubAppState,
        ]);
        $response   = ObjectManager::getInstance()->create(HttpResponse::class);
        $controller->execute()->renderResult($response);

        return json_decode((string) $response->getBody(), true);
    }

    public function testHidesExceptionMessagesInProductionMode(): void
    {
        $result = $this->executePaging(AppState::MODE_PRODUCTION);

        $this->assertSame('', $result['grid_html']);
        $this->assertSame('The grid could not be loaded.', $result['message']);
    }

    public function testShowsExceptionMessagesInDeveloperMode(): void
    {
        $result = $this->executePaging(AppState::MODE_DEVELOPER);

        $this->assertSame('', $result['grid_html']);
        $this->assertStringContainsString(self::GRID_WITHOUT_CONFIG, $result['message']);
    }
}
