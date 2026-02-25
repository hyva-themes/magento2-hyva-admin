<?php
declare(strict_types=1);

namespace Hyva\Admin\Model\Config\Source;

use Magento\Customer\Model\Customer\Attribute\Source\GroupSourceLoggedInOnlyInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer group Options
 */
class Group implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var GroupSourceLoggedInOnlyInterface
     */
    private $groupSourceLoggedInOnly;

    /**
     * @param GroupSourceLoggedInOnlyInterface $groupSourceForLoggedInCustomers
     */
    public function __construct(GroupSourceLoggedInOnlyInterface $groupSourceForLoggedInCustomers)
    {
        $this->groupSourceLoggedInOnly = $groupSourceForLoggedInCustomers;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->groupSourceLoggedInOnly->toOptionArray();
            array_unshift($this->options, ['value' => '0', 'label' => __('-- Please Select --')]);
        }

        return $this->options;
    }
}
