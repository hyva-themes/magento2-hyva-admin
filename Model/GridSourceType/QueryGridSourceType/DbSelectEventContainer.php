<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\QueryGridSourceType;

use Magento\Framework\DB\Select;

class DbSelectEventContainer
{
    /**
     * @var Select
     */
    private $select;

    public function __construct(Select $select)
    {

        $this->select = $select;
    }

    public function getSelect(): Select
    {
        return $this->select;
    }

    public function replaceSelect(Select $select): void
    {
        $this->select = $select;
    }
}
