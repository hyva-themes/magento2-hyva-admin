<?php

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

class SourceIterator implements \Iterator
{
    protected SearchCriteriaInterface $searchCriteria;
    protected int $total;
    protected NavigationInterface $navigation;

    private HyvaGridInterface $grid;

    private array $currentBatch = [];
    private int $currentCounter=0;

    public function __construct(HyvaGridInterface $grid)
    {
        $this->grid = $grid;
        $this->navigation = $grid->getNavigation();
        $this->searchCriteria = $this->navigation->getSearchCriteria();
        $this->searchCriteria->setPageSize(200);
        $this->total = $this->navigation->getTotalRowsCount();
    }

    public function current(): RowInterface
    {
        if(!isset($this->currentBatch[$this->currentCounter])){
            $this->currentBatch = [];
            $page =  (int) ceil($this->currentCounter / $this->searchCriteria->getPageSize());
            $this->searchCriteria->setCurrentPage($page + 1);
            $inBatchCounter = 0;
            foreach( $this->grid->getRows() as $row){
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
