<?php

namespace Hyva\Admin\Model;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ExportInterface
{

    /**
     * @return string
     */
    public function getFileName(): string;

    /**
     * @param $fileName
     * @return ExportInterface
     */
    public function setFileName($fileName): ExportInterface;

    /**
     * @return string
     */
    public function getMetaType(): string;

    /**
     * @param $metaType
     * @return ExportInterface
     */
    public function setMetaType($metaType): ExportInterface;

    /**
     * @return bool
     */
    public function create();

    /**
     * @return string
     */
    public function getRootDir(): string;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return ExportInterface
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): ExportInterface;

    /**
     * @return SearchCriteriaInterface
     */
    public function getSearchCriteria(): SearchCriteriaInterface;

}