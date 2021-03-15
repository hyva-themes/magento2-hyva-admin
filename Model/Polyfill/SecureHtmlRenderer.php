<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Polyfill;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * This class is a polyfill for Magento 2.3 compatibility.
 *
 * It does not replicate the full capabilities of \Magento\Framework\View\Helper\SecureHtmlRenderer, but
 * rather provides just enough logic to render the script tag from view/adminhtml/templates/page/js/require_js.phtml.
 *
 * Note: The tag-processors used by the original class are NOT CALLED by this polyfill.
 */
class SecureHtmlRenderer implements ArgumentInterface
{
    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
    }

    public function renderTag(
        string $tagName,
        array $attributes,
        ?string $content = null,
        bool $isTextContent = true
    ): string {
        $attributesHtmls = [];
        foreach ($attributes as $attribute => $value) {
            $attributesHtmls[] = $attribute . '="' . $this->escaper->escapeHtmlAttr($value) . '"';
        }
        $tagContent = null;
        if ($content !== null) {
            $tagContent = $isTextContent ? $this->escaper->escapeHtml($content) : $content;
        }
        $attributesHtml = '';
        if ($attributesHtmls) {
            $attributesHtml = ' ' . implode(' ', $attributesHtmls);
        }

        $html = '<' . $tagName . $attributesHtml;
        if ($tagContent) {
            $html .= '>' . $content . '</' . $tagName . '>';
        } else {
            $html .= '/>';
        }

        return $html;
    }
}
