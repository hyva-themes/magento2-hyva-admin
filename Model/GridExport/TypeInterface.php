<?php

namespace Hyva\Admin\Model\GridExport;

use Hyva\Admin\ViewModel\HyvaGridInterface;

interface TypeInterface
{

    public function getFileName(): string;

    public function getContentType(): string;

    public function createFileToDownload();

    public function getRootDir(): string;

    public function getGrid(): HyvaGridInterface;

}