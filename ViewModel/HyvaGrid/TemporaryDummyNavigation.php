<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\HyvaGridSourceInterface;

class TemporaryDummyNavigation implements NavigationInterface
{
    private HyvaGridSourceInterface $gridSource;

    public function __construct(HyvaGridSourceInterface $gridSource)
    {
        $this->gridSource = $gridSource;
    }

    public function getTotalRowsCount(): int
    {
        return 100;
    }

    public function getPageCount(): int
    {
        return 1;
    }

    public function getNumberOfRowsPerPage(): int
    {
        return $this->getTotalRowsCount();
    }

    public function getCurrentPageNumber(): int
    {
        return 0;
    }

    public function hasPreviousPage(): bool
    {
        return false;
    }

    public function getPreviousPageUrl(): string
    {
        return '';
    }

    public function hasNextPage(): bool
    {
        return false;
    }

    public function getNextPageUrl(): string
    {
        return '';
    }
}
