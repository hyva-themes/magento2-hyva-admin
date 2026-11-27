<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\HyvaGridConfigReaderInterface;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\AuthorizationInterface;

/**
 * Decides if the current admin user may see a grid.
 *
 * Grids can declare an ACL resource with <grid acl="Vendor_Module::resource">. Without it, any admin user may see
 * the grid on the pages where it is rendered.
 *
 * The ajax paging and the export endpoints receive the grid name as a request parameter, so they could be used
 * to load any grid. To bind them to the ACL of the pages the grids are rendered on, the names of the grids that
 * were rendered during the admin session are recorded. The endpoints only serve those grids.
 */
class GridAuthorization
{
    private const SESSION_KEY = 'hyva_admin_rendered_grids';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var HyvaGridConfigReaderInterface
     */
    private $gridConfigReader;

    /**
     * @var BackendSession
     */
    private $session;

    /**
     * @var string[]
     */
    private $memoizedAclResources = [];

    public function __construct(
        AuthorizationInterface $authorization,
        HyvaGridConfigReaderInterface $gridConfigReader,
        BackendSession $session
    ) {
        $this->authorization    = $authorization;
        $this->gridConfigReader = $gridConfigReader;
        $this->session          = $session;
    }

    /**
     * Check the ACL resource declared on the grid, if any.
     */
    public function isAllowed(string $gridName): bool
    {
        $aclResource = $this->getAclResource($gridName);

        return $aclResource === '' || $this->authorization->isAllowed($aclResource);
    }

    private function getAclResource(string $gridName): string
    {
        if (!isset($this->memoizedAclResources[$gridName])) {
            $config = $this->gridConfigReader->getGridConfiguration($gridName);

            $this->memoizedAclResources[$gridName] = (string) ($config['acl'] ?? '');
        }

        return $this->memoizedAclResources[$gridName];
    }

    /**
     * Record that the grid was rendered for the current admin user, so the paging and export endpoints may serve it.
     */
    public function registerRenderedGrid(string $gridName): void
    {
        $renderedGrids = $this->getRenderedGrids();
        if (!in_array($gridName, $renderedGrids, true)) {
            $renderedGrids[] = $gridName;
            $this->session->setData(self::SESSION_KEY, $renderedGrids);
        }
    }

    /**
     * Check if the paging and export endpoints may serve the grid to the current admin user.
     *
     * This requires the grid to be allowed and to have been rendered during the current admin session.
     */
    public function isAllowedForEndpoint(string $gridName): bool
    {
        return $gridName !== ''
            && in_array($gridName, $this->getRenderedGrids(), true)
            && $this->isAllowed($gridName);
    }

    private function getRenderedGrids(): array
    {
        $renderedGrids = $this->session->getData(self::SESSION_KEY);

        return is_array($renderedGrids) ? $renderedGrids : [];
    }
}
