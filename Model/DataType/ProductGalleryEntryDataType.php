<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;

class ProductGalleryEntryDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const TYPE_MAGENTO_PRODUCT_GALLERY_ENTRY = 'magento_product_gallery_entry';

    private ImageUrlBuilder $imageUrlBuilder;

    public function __construct(ImageUrlBuilder $imageUrlBuilder)
    {
        $this->imageUrlBuilder = $imageUrlBuilder;
    }

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductMediaGalleryEntryInstance($value)
            ? self::TYPE_MAGENTO_PRODUCT_GALLERY_ENTRY
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isProductMediaGalleryEntryClassName($type)
            ? self::TYPE_MAGENTO_PRODUCT_GALLERY_ENTRY
            : null;

    }

    private function isProductMediaGalleryEntryInstance($value): bool
    {
        return is_object($value) && $value instanceof ProductAttributeMediaGalleryEntryInterface;
    }

    private function isProductMediaGalleryEntryClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, ProductAttributeMediaGalleryEntryInterface::class);
    }

    /**
     * @param ProductAttributeMediaGalleryEntryInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatGalleryEntry($value)
            : null;
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->toString($value)
            : null;
    }

    private function formatGalleryEntry(ProductAttributeMediaGalleryEntryInterface $value): string
    {
        return sprintf(
            '<img src="%s" alt="%s"/>',
            $this->imageUrlBuilder->getUrl($value->getFile(), 'thumbnail'),
            $value->getLabel()
        );
    }
}
