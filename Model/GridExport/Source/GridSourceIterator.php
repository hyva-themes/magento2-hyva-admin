<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Source;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

class GridSourceIterator implements \Iterator
{
    /**
     * @var SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var int
     */
    protected $total;

    /**
     * @var NavigationInterface
     */
    protected $navigation;

    /**
     * @var HyvaGridExportInterface
     */
    private $grid;

    /**
     * @var array
     */
    private $currentBatch = [];

    /**
     * @var int
     */
    private $currentCounter = 0;

    public function __construct(HyvaGridExportInterface $grid)
    {
        $this->grid           = $grid;
        $this->searchCriteria = $grid->getSearchCriteria();
        $this->searchCriteria->setPageSize(200);
        $this->total = $grid->getTotalRowsCount();
    }

    public function current(): RowInterface
    {
        if (!isset($this->currentBatch[$this->currentCounter])) {
            $this->currentBatch = [];
            $page               = (int) ceil($this->currentCounter / $this->searchCriteria->getPageSize());
            $this->searchCriteria->setCurrentPage($page + 1);
            $inBatchCounter = 0;
            foreach ($this->grid->getRowsForSearchCriteria($this->searchCriteria) as $row) {
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
