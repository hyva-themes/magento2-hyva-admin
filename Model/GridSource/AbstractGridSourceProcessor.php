<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSource;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Generic parent implementation of HyvaGridSourceProcessorInterface.
 *
 * Extend this class if you don't need both methods in your processor.
 *
 * If extending this class, you don't need to also call this parent class methods.
 */
abstract class AbstractGridSourceProcessor implements HyvaGridSourceProcessorInterface
{
    public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
    {

    }

    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
    {
        return $rawResult;
    }
}
