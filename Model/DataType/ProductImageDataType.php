<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Hyva\Admin\Model\ProductMediaInBackend;

/**
 * This data type can not be automatically determined, it must be configured as the column type in the grid
 */
class ProductImageDataType implements DataTypeValueToStringConverterInterface
{
    public const TYPE_MAGENTO_PRODUCT_GALLERY = 'magento_product_image';

    /**
     * @var ProductMediaInBackend
     */
    private $productMedia;

    public function __construct(ProductMediaInBackend $productMedia)
    {
        $this->productMedia = $productMedia;
    }

    public function toString($value): ?string
    {
        return $value
            ? $this->productMedia->getImageUrl($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $value
            ? $this->productMedia->getImageHtmlElement($value, 'Product Image')
            : null;
    }
}
