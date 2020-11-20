<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductDataType implements DataTypeGuesserInterface, DataTypeValueToStringConverterInterface
{
    const MAGENTO_PRODUCT = 'magento_product';

    public function typeOf($value): ?string
    {
        return is_object($value) && $value instanceof ProductInterface
            ? self::MAGENTO_PRODUCT
            : null;
    }

    /**
     * @param ProductInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->typeOf($value)
            ? $this->getDisplayName($value)
            : null;
    }

    private function getDisplayName(ProductInterface $product): string
    {
        return (string) ($product->getName() ?? $product->getSku() ?? $product->getId() ?? '(not initialized)');
    }

    public function toStringRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->typeOf($value)
            ? sprintf('%s [SKU %s]', $this->getDisplayName($value), $value->getSku() ?? '?')
            : null;
    }
}
