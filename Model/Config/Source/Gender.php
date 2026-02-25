<?php
declare(strict_types=1);

namespace Hyva\Admin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer gender options
 */
class Gender implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => '1', 'label' => __('Male')],
                ['value' => '2', 'label' => __('Female')],
                ['value' => '3', 'label' => __('Not Specified')],
            ];
        }

        return $this->options;
    }
}
