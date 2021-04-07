<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Escaper;
use Magento\Framework\View\DesignInterface;

class ProductMediaInBackend
{
    /**
     * @var ImageUrlBuilder
     */
    private $imageUrlBuilder;

    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(
        ImageUrlBuilder $imageUrlBuilder,
        Escaper $escaper
    ) {
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->escaper         = $escaper;
    }

    public function getImageHtmlElement(
        string $file,
        string $altText,
        string $productImageType = 'product_listing_thumbnail'
    ): string {
        return sprintf(
            '<img src="%s" alt="%s" loading="lazy" />',
            $this->getImageUrl($file, $productImageType),
            $this->escaper->escapeHtmlAttr($altText)
        );
    }

    public function getImageUrl(string $file, string $productImageType = 'product_listing_thumbnail'): string
    {
        return $this->imageUrlBuilder->getUrl($file, $productImageType);
    }
}
