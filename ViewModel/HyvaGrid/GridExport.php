<?php

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\UrlInterface as UrlBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class GridExport implements GridExportInterface
{
    private LayoutInterface $layout;

    private string $id;

    private ?string $label;

    private ?string $enabled;

    private ?string $template;

    private ?string $fileName;

    private ?string $sortOrder;

    public function __construct(
        LayoutInterface $layout,
        string $id,
        ?string $label,
        ?string $fileName = null,
        ?string $enabled = null,
        ?string $template = null,
        ?string $sortOrder = null
    ) {
        $this->layout     = $layout;
        $this->id         = $id;
        $this->label      = $label;
        $this->enabled    = $enabled;
        $this->template   = $template;
        $this->fileName = $fileName;
        $this->sortOrder = $sortOrder;
    }

    public function getHtml(): string
    {
        return $this->enabled !== 'false'
            ? $this->createTemplateBlock()->toHtml()
            : '';
    }

    private function createTemplateBlock(): Template
    {
        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class);
        $block->setTemplate($this->getTemplate() ?? 'Hyva_Admin::grid/button.phtml');
        $block->assign('button', $this);

        return $block;
    }

    private function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function getFileName(): string
    {
        return (string)$this->fileName;
    }
}