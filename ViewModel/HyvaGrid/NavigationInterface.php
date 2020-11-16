<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface NavigationInterface
{
    public function getTotalRowsCount(): int;

    public function getPageCount(): int;

    public function getNumberOfRowsPerPage(): int;

    public function getCurrentPageNumber(): int;

    public function hasPreviousPage(): bool;

    public function getPreviousPageUrl(): string;

    public function hasNextPage(): bool;

    public function getNextPageUrl(): string;
}
