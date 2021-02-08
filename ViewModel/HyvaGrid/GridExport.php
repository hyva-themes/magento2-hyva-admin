<?php
/**
 * GridExport
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\UrlInterface as UrlBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class GridExport implements GridExportInterface
{
    private LayoutInterface $layout;

    private string $id;

    private ?string $label;

    private ?string $url;

    private ?string $onclick;

    private ?string $enabled;

    private ?string $template;

    /**
     * @var UrlBuilder
     */
    private UrlBuilder $urlBuilder;

    public function __construct(
        LayoutInterface $layout,
        UrlBuilder $urlBuilder,
        string $id,
        ?string $label = null,
        ?string $url = null,
        ?string $onclick = null,
        ?string $enabled = null,
        ?string $template = null
    ) {
        $this->layout     = $layout;
        $this->urlBuilder = $urlBuilder;
        $this->id         = $id;
        $this->label      = $label;
        $this->url        = $url;
        $this->onclick    = $onclick;
        $this->enabled    = $enabled;
        $this->template   = $template;
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

    public function getUrl(): string
    {
        return isset($this->url) ? $this->urlBuilder->getUrl($this->url) : '';
    }

    public function getOnClick(): ?string
    {
        return $this->onclick;
    }
}