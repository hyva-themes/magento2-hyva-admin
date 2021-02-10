<?php
/**
 * SourceIterator
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGridInterface;

class SourceIterator implements \Iterator
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteria;
    protected $total;
    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface
     */
    protected $navigation;

    /**
     * @var HyvaGridInterface
     */
    private $grid;

    private $currentBatch=0;
    private $currentCounter=0;

    public function __construct(HyvaGridInterface $grid)
    {
        $this->grid = $grid;
        $this->navigation = $grid->getNavigation();
        $this->searchCriteria = $this->navigation->getSearchCriteria();
        $this->searchCriteria->setPageSize(200);
        $this->total = $this->navigation->getTotalRowsCount();
    }

    public function current()
    {
        if(!isset($this->currentBatch[$this->currentCounter])){
            $this->currentBatch = [];
            $page =  (int) ceil($this->currentCounter / $this->searchCriteria->getPageSize());
            $this->searchCriteria->setCurrentPage($page + 1);
            $inBatchCounter = 0;
            foreach( $this->grid->getRows(true) as $row){
                $this->currentBatch[$this->currentCounter + $inBatchCounter] = $row;
                ++$inBatchCounter;
            }
        }
        return $this->currentBatch[$this->currentCounter];
    }

    public function next()
    {
        ++$this->currentCounter;
    }

    public function key()
    {
        return $this->currentCounter;
    }

    public function valid()
    {
        return $this->key() < $this->total;
    }

    public function rewind()
    {
        $this->currentCounter = 0;
    }
}