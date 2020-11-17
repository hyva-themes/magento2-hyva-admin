<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

/**
 * Implement this interface and specify that class as an array source type for a hyva grid.
 * Return an array with one sub-array for each row of the grid.
 */
interface HyvaGridArrayProviderInterface
{
    /**
     * @return array[]
     */
    public function getHyvaGridData(): array;
}
