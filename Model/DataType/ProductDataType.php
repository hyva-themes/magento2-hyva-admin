<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductDataType implements DataTypeInterface
{
    public const MAGENTO_PRODUCT = 'magento_product';

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductInstance($value)
            ? self::MAGENTO_PRODUCT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isProductClassName($type)
            ? self::MAGENTO_PRODUCT
            : null;

    }

    private function isProductInstance($value): bool
    {
        return is_object($value) && $value instanceof ProductInterface;
    }

    private function isProductClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, ProductInterface::class);
    }

    /**
     * @param ProductInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->getDisplayName($value)
            : null;
    }

    private function getDisplayName(ProductInterface $product): string
    {
        return (string) ($product->getName() ?? $product->getSku() ?? $product->getId() ?? '(not initialized)');
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? sprintf('%s [SKU %s]', $this->getDisplayName($value), $value->getSku() ?? '?')
            : null;
    }
}
