<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Model\Product\Type\AbstractType as AbstractProductType;

class ProductTypeInstanceDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_PRODUCT_TYPE = 'magento_product_type';

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductTypeInstance($value)
            ? self::TYPE_MAGENTO_PRODUCT_TYPE
            : null;
    }

    private function isProductTypeInstance($value): bool
    {
        return is_object($value) && $value instanceof AbstractProductType;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_MAGENTO_PRODUCT_TYPE || $this->isProductTypeClass($type)
            ? self::TYPE_MAGENTO_PRODUCT_TYPE
            : null;
    }

    private function isProductTypeClass(string $type): bool
    {
        return ltrim($type, '\\') === AbstractProductType::class || is_subclass_of($type, AbstractProductType::class);
    }

    /**
     * @param AbstractProductType|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->isProductTypeInstance($value)
            ? str_replace(['\Interceptor', '\Proxy'], '', get_class($value))
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
