<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\GridAuthorization;
use Hyva\Admin\Test\Integration\TestingGridConfig;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\AuthorizationInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class GridAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        TestingGridConfig::install();
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
    }

    protected function tearDown(): void
    {
        ObjectManager::getInstance()->get(BackendSession::class)->clearStorage();
        TestingGridConfig::uninstall();
    }

    private function createGridAuthorization(bool $isAclResourceAllowed = true): GridAuthorization
    {
        $stubAuthorization = $this->createStub(AuthorizationInterface::class);
        $stubAuthorization->method('isAllowed')->willReturnCallback(
            function (string $resource) use ($isAclResourceAllowed): bool {
                $this->assertSame(TestingGridConfig::ACL_RESOURCE, $resource);
                return $isAclResourceAllowed;
            }
        );

        return ObjectManager::getInstance()->create(GridAuthorization::class, [
            'authorization' => $stubAuthorization,
        ]);
    }

    public function testAllowsGridsWithoutAclResource(): void
    {
        $this->assertTrue($this->createGridAuthorization(false)->isAllowed(TestingGridConfig::GRID));
    }

    public function testChecksTheAclResourceOfTheGrid(): void
    {
        $this->assertTrue($this->createGridAuthorization(true)->isAllowed(TestingGridConfig::ACL_GRID));
        $this->assertFalse($this->createGridAuthorization(false)->isAllowed(TestingGridConfig::ACL_GRID));
    }

    public function testDeniesEndpointAccessToGridsThatWereNotRendered(): void
    {
        $sut = $this->createGridAuthorization();

        $this->assertFalse($sut->isAllowedForEndpoint(TestingGridConfig::GRID));
        $this->assertFalse($sut->isAllowedForEndpoint(''));
    }

    public function testAllowsEndpointAccessToRenderedGrids(): void
    {
        $sut = $this->createGridAuthorization();
        $sut->registerRenderedGrid(TestingGridConfig::GRID);

        $this->assertTrue($sut->isAllowedForEndpoint(TestingGridConfig::GRID));
        $this->assertFalse($sut->isAllowedForEndpoint(TestingGridConfig::ACL_GRID));
    }

    public function testKeepsRenderedGridsInTheAdminSession(): void
    {
        $this->createGridAuthorization()->registerRenderedGrid(TestingGridConfig::GRID);

        $this->assertTrue($this->createGridAuthorization()->isAllowedForEndpoint(TestingGridConfig::GRID));
    }

    public function testDeniesEndpointAccessToRenderedGridsIfTheAclResourceIsDenied(): void
    {
        $sut = $this->createGridAuthorization(false);
        $sut->registerRenderedGrid(TestingGridConfig::ACL_GRID);

        $this->assertFalse($sut->isAllowedForEndpoint(TestingGridConfig::ACL_GRID));
    }
}
