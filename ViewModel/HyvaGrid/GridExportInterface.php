<?php

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridExportInterface
{
    public function getHtml(): string;

    public function getId(): string;

    public function getLabel(): string;

    public function getFileName(): string;
}