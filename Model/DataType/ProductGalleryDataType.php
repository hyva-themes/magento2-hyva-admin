<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Hyva\Admin\Model\ProductMediaInBackend;

use function array_map as map;

class ProductGalleryDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_PRODUCT_GALLERY = 'magento_product_gallery';

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

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function buildImageTag(array $galleryImage): string
    {
        $file = $galleryImage['file'] ?? '';
        return $file
            ? $this->productMedia->getImageHtmlElement($file, 'Gallery Entry')
            : '';
    }
}
