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
     * @param string $gridName
     * @param SearchCriteriaInterface $searchCriteria
     * @param mixed $source
     */
    public function beforeLoad(string $gridName, SearchCriteriaInterface $searchCriteria, $source): void;

    /**
     * Provide the ability to change the raw grid result after it is loaded.
     *
     * The method must return the new result or null. The $resutl type depends on the grid configuration.
     * (If null is returned, the result value from before afterLoad is used).
     *
     * @param string $gridName
     * @param SearchCriteriaInterface $searchCriteria
     * @param mixed $rawResult
     * @return mixed
     */
    public function afterLoad(string $gridName, SearchCriteriaInterface $searchCriteria, $rawResult);
}
