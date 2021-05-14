<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;

/**
 * Collection grid source type specific processor interface.
 */
interface HyvaGridCollectionProcessorInterface extends HyvaGridSourceProcessorInterface
{
    /**
     * This interface provides an afterInitSelect() callback that is only applicable to collection grid sources.
     *
     * This callback is triggered every time the collection grid source is instantiated, before the search
     * criteria is applied. It is intended to allow joining additional fields that will then be available
     * as grid columns.
     *
     * @param AbstractDbCollection $source
     * @param string $gridName
     */
    public function afterInitSelect(AbstractDbCollection $source, string $gridName): void;
}
