<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Escaper;
use Magento\Framework\View\DesignInterface;

class BackendProductMedia
{
    private AppState $appState;

    private ImageUrlBuilder $imageUrlBuilder;

    private Escaper $escaper;

    private DesignInterface $design;

    public function __construct(
        AppState $appState,
        DesignInterface $design,
        ImageUrlBuilder $imageUrlBuilder,
        Escaper $escaper
    ) {
        $this->appState        = $appState;
        $this->design          = $design;
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->escaper         = $escaper;
    }

    public function getImageUrl(string $file, string $productImageType = 'product_page_image_small'): string
    {
        return $this->appState->emulateAreaCode('frontend', function () use ($file, $productImageType): string {

            $resetDesign = $this->setFrontendTheme('Magento/blank');

            $url = $this->imageUrlBuilder->getUrl($file, $productImageType);

            $resetDesign();

            return $url;
        });
    }

    private function setFrontendTheme(string $theme): callable
    {
        $currentDesignArea = $this->design->getArea();
        $currentTheme      = $this->design->getDesignTheme();

        $this->design->setDesignTheme($theme, 'frontend');

        return function () use ($currentDesignArea, $currentTheme) {
            $this->design->setDesignTheme($currentTheme, $currentDesignArea);
        };
    }

    public function getImageHtmlElement(string $file, string $altText): string
    {
        return sprintf('<img src="%s" alt="%s"/>', $this->getImageUrl($file), $this->escaper->escapeHtmlAttr($altText));
    }
}
