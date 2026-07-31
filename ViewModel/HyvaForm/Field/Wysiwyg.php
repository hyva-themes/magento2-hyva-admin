<?php declare(strict_types=1);
namespace Hyva\Admin\ViewModel\HyvaForm\Field;

use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Wysiwyg implements ArgumentInterface
{
    public function __construct(
        private WysiwygConfig $wysiwygConfig,
        private Escaper $escaper,
    ) {}

    public function getWysiwygConfig(string $fieldName): \Magento\Framework\DataObject
    {
        return $this->wysiwygConfig->getConfig([
            'textarea_name'   => $fieldName,
            'textarea_id'     => $fieldName,
            'add_widgets'     => true,
            'add_variables'   => true,
            'add_images'      => true,
            'add_directives'  => true,
            'use_container'   => true,
            'container_class' => 'hor-scroll',
        ]);
    }

    public function getWysiwygConfigJson(string $fieldName): string
    {
        return $this->escaper->escapeJs(
            json_encode($this->getWysiwygConfig($fieldName)->getData())
        );
    }
}