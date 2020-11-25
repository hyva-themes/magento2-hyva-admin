<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;

use function array_map as map;

class GalleryDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_MAGENTO_PRODUCT_GALLERY = 'magento_product_gallery';

    private ImageUrlBuilder $imageUrlBuilder;

    public function __construct(ImageUrlBuilder $imageUrlBuilder)
    {
        $this->imageUrlBuilder = $imageUrlBuilder;
    }

    public function valueToTypeCode($value): ?string
    {
        return null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === 'gallery'
            ? self::TYPE_MAGENTO_PRODUCT_GALLERY
            : null;
    }

    public function toString($value): ?string
    {
        return implode('', map([$this, 'buildImageTag'], $value['images'] ?? []));
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function buildImageTag(array $galleryImage): string
    {
        $file = $galleryImage['file'] ?? '';
        return $file
            ? sprintf('<img src="%s" alt="Product Gallery Value"/>', $this->imageUrlBuilder->getUrl($file, 'thumbnail'))
            : '';
    }
}
