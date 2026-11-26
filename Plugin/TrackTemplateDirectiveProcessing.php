<?php declare(strict_types=1);

namespace Hyva\Admin\Plugin;

use Hyva\Admin\Model\TemplateDirectiveState;
use Magento\Framework\Filter\Template as TemplateFilter;

/**
 * All template directives (email, CMS, widget and Page Builder filters) are processed by
 * \Magento\Framework\Filter\Template::filter() or a subclass.
 */
class TrackTemplateDirectiveProcessing
{
    /**
     * @var TemplateDirectiveState
     */
    private $templateDirectiveState;

    public function __construct(TemplateDirectiveState $templateDirectiveState)
    {
        $this->templateDirectiveState = $templateDirectiveState;
    }

    /**
     * @param TemplateFilter $subject
     * @param callable $proceed
     * @param mixed $value
     * @return mixed
     */
    public function aroundFilter(TemplateFilter $subject, callable $proceed, $value)
    {
        $this->templateDirectiveState->start();
        try {
            return $proceed($value);
        } finally {
            $this->templateDirectiveState->end();
        }
    }
}
