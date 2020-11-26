<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Hyva\Admin\Model\ProductMedia;

/**
 * This data type can not be automatically determined, it must be configured as the column type in the grid
 */
class ProductImageDataType implements DataTypeValueToStringConverterInterface
{
    const TYPE_MAGENTO_PRODUCT_GALLERY = 'magento_product_image';

    private ProductMedia $productMedia;

    public function __construct(ProductMedia $productMedia)
    {
        $this->productMedia = $productMedia;
    }

    public function toString($value): ?string
    {
        return $value ?
            $this->buildImageTag($value) :
            null;
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function buildImageTag(string $file): string
    {
        return $this->productMedia->getImageHtmlElement($file, 'Product Image');
    }
}
