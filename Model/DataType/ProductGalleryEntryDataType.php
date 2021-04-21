<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Hyva\Admin\Model\ProductMediaInBackend;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;

class ProductGalleryEntryDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_PRODUCT_GALLERY_ENTRY = 'magento_product_gallery_entry';

    /**
     * @var ProductMediaInBackend
     */
    private $productMedia;

    public function __construct(ProductMediaInBackend $productMedia)
    {
        $this->productMedia = $productMedia;
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
            ? $this->productMedia->getImageHtmlElement($value->getFile(), (string) $value->getLabel())
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->toString($value)
            : null;
    }
}
