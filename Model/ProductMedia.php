<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Escaper;
use Magento\Framework\View\DesignLoader;

class ProductMedia
{
    private AppState $appState;

    private DesignLoader $designLoader;

    private ImageUrlBuilder $imageUrlBuilder;

    private Escaper $escaper;

    public function __construct(
        AppState $appState,
        DesignLoader $designLoader,
        ImageUrlBuilder $imageUrlBuilder,
        Escaper $escaper
    ) {
        $this->appState        = $appState;
        $this->designLoader    = $designLoader;
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->escaper = $escaper;
    }

    public function getImageUrl(string $file): string
    {
        return $this->appState->emulateAreaCode('frontend', function () use ($file): string {
            $this->designLoader->load();
            return $this->imageUrlBuilder->getUrl($file, 'product_page_image_small');
        });
    }

    public function getImageHtmlElement(string $file, string $altText): string
    {
        return sprintf('<img src="%s" alt="%s"/>', $this->getImageUrl($file), $this->escaper->escapeHtmlAttr($altText));
    }
}
