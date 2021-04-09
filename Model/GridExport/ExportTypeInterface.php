<?php

namespace Hyva\Admin\Model\GridExport;

use Hyva\Admin\ViewModel\HyvaGridInterface;

interface ExportTypeInterface
{

    public function getFileName(): string;

    public function getContentType(): string;

    public function createFileToDownload();

    public function getRootDir(): string;

    public function getGrid(): HyvaGridInterface;

}
