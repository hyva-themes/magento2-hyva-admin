<?php
/**
 * Csv
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;

class Csv extends AbstractExport
{

    protected $fileName = "export.csv";

    public function create()
    {
        $navigation = $this->getGrid()->getNavigation();
        $pageCount = $navigation->getPageCount();
        for ($i = 1; $i <= $pageCount; $i++) {
            $navigation->getSearchCriteria()->setCurrentPage($i);
            $rows = $this->getGrid()->getRows();
            foreach ($rows as $row) {
                $line = array_map(function (CellInterface $cell) {
                    return $cell->getTextValue();
                }, $row->getCells());
                @file_put_contents($this->fileName, implode(",", $line), FILE_APPEND);
            }
        }
    }
}