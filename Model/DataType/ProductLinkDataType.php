<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Framework\Reflection\DataObjectProcessor;

class ProductLinkDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_PRODUCT_LINK = 'magento_product_link';

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductLinkInstance($value)
            ? self::TYPE_MAGENTO_PRODUCT_LINK
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isProductLinkClassName($type)
            ? self::TYPE_MAGENTO_PRODUCT_LINK
            : null;

    }

    private function isProductLinkInstance($value): bool
    {
        return is_object($value) && $value instanceof ProductLinkInterface;
    }

    private function isProductLinkClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, ProductLinkInterface::class);
    }

    /**
     * @param ProductLinkInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatProductLink($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->toString($value)
            : null;
    }

    private function formatProductLink(ProductLinkInterface $value): string
    {
        return sprintf('[%s: %s]', $value->getLinkType(), $value->getLinkedProductSku());
    }
}
