<?php

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGridInterface;

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

    public function create();

    /**
     * @return string
     */
    public function getRootDir(): string;

    /**
     * @param HyvaGridInterface $grid
     * @return ExportInterface
     */
    public function setGrid(HyvaGridInterface $grid): ExportInterface;

    /**
     * @return HyvaGridInterface
     */
    public function getGrid(): HyvaGridInterface;

}