<?php

namespace Hyva\Admin\Model\GridExport;

interface ExportTypeInterface
{

    public function getFileName(): string;

    public function getContentType(): string;

    public function createFileToDownload();

    public function getExportDir(): string;
}
