<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

/**
 * Tracks whether template directives like {{block}} or {{layout}} are being processed.
 *
 * Admin users with access to email template previews, the CMS WYSIWYG editor or Page Builder can render blocks
 * with arbitrary arguments through template directives. Admin grids must not be rendered that way, see
 * \Hyva\Admin\Block\BaseHyvaGrid::toHtml().
 */
class TemplateDirectiveState
{
    /**
     * @var int
     */
    private $depth = 0;

    public function start(): void
    {
        $this->depth++;
    }

    public function end(): void
    {
        $this->depth = max(0, $this->depth - 1);
    }

    public function isProcessingDirectives(): bool
    {
        return $this->depth > 0;
    }
}
