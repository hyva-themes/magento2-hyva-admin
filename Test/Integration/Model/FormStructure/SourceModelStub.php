<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormStructure;

use Magento\Framework\Data\OptionSourceInterface;

class SourceModelStub implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 123, 'label' => 'Test Label A'],
            ['value' => 124, 'label' => 'Test Label B']
        ];
    }
}
