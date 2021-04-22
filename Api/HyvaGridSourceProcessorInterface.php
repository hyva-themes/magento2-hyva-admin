<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface HyvaGridSourceProcessorInterface
{
    /**
     * Provide the ability to mutate the grid $source before the grid data is loaded.
     *
     * The type of $source is grid configuration dependent.
     *
     * @param mixed $source
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     */
    public function beforeLoad( $source, SearchCriteriaInterface $searchCriteria, string $gridName): void;

    /**
     * Provide the ability to change the raw grid result after it is loaded.
     *
     * The method must return the new result or null. The $resutl type depends on the grid configuration.
     * (If null is returned, the result value from before afterLoad is used).
     *
     * @param mixed $rawResult
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     * @return mixed
     */
    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName);
}
