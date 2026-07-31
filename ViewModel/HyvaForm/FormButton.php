<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormButton implements FormButtonInterface
{
    /**
     * @var array
     */
    private $buttonConfig;

    public function __construct(array $buttonConfig = [])
    {
        $this->buttonConfig = $buttonConfig;
    }

    public function getHtml(): string
    {
        $label = $this->getLabel();
        $attributes = $this->buildButtonAttributes();
        
        return sprintf(
            '<button %s>%s</button>',
            $attributes,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    public function getLabel(): string
    {
        return $this->buttonConfig['label'] ?? '';
    }

    public function getUrl(): ?string
    {
        return $this->buttonConfig['url'] ?? null;
    }

    public function getId(): ?string
    {
        return $this->buttonConfig['id'] ?? null;
    }
    public function getOnclick(): ?string
    {
        return $this->buttonConfig['onclick'] ?? null;
    }
    public function getUseajax(): ?bool
    {
        return $this->buttonConfig['useajax'] ?? null;
    }
    private function buildButtonAttributes(): string
    {
        $attributes = [];
        
        // Set button type
        $attributes[] = 'type="button"';
        
        // Add ID if present
        if (!empty($this->buttonConfig['id'])) {
            $attributes[] = sprintf('id="%s"', htmlspecialchars($this->buttonConfig['id'], ENT_QUOTES, 'UTF-8'));
        }
        
        // Add name if present
        if (!empty($this->buttonConfig['name'])) {
            $attributes[] = sprintf('name="%s"', htmlspecialchars($this->buttonConfig['name'], ENT_QUOTES, 'UTF-8'));
        }
        
        // Add CSS classes if present
        $classes = ['btn', 'btn-primary'];
        if (!empty($this->buttonConfig['class'])) {
            $classes[] = $this->buttonConfig['class'];
        }
        if ($this->isDisabled()) {
            $classes[] = 'disabled';
        }
        
        if (!empty($classes)) {
            $attributes[] = sprintf('class="%s"', htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8'));
        }
        
        // Add onclick/action if URL is present
        if (!empty($this->buttonConfig['url'])) {
            // Build full URL and escape it using Magento escaper
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $urlBuilder = $om->get(\Magento\Framework\UrlInterface::class);
            $escaper = $om->get(\Magento\Framework\Escaper::class);

            $rawUrl = $this->buttonConfig['url'];
            // If the url looks like a route (no scheme), build using urlBuilder
            if (strpos($rawUrl, 'http://') === 0 || strpos($rawUrl, 'https://') === 0) {
                $fullUrl = $rawUrl;
            } else {
                $fullUrl = $urlBuilder->getUrl($rawUrl);
            }

            $escapedUrl = $escaper->escapeUrl($fullUrl); 
            $attributes[] = '@click="submitForm($event,\''.$escapedUrl.'\')"';
        } 
        
        if (!empty($this->buttonConfig['onclick'])) {
            $attributes[] = sprintf('onclick="%s"', htmlspecialchars($this->buttonConfig['onclick'], ENT_QUOTES, 'UTF-8'));
        }
        
        // Add disabled attribute if not enabled
        if ($this->isDisabled()) {
            $attributes[] = 'disabled="disabled"';
        }
       
         if (!empty($this->buttonConfig['useajax'])) {
             $attributes[] = 'attr-ajax="'.$this->buttonConfig['useajax'].'"'; 
        }
        
        return implode(' ', $attributes);
    }

    private function isDisabled(): bool
    {
        $enabled = $this->buttonConfig['enabled'] ?? 'true';
        return $enabled === 'false' || $enabled === false || $enabled === 0;
    }
}
