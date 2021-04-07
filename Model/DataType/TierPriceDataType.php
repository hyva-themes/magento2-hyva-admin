<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;

use function array_keys as keys;
use function array_map as map;

class TierPriceDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_TIER_PRICE = 'magento_tier_price';

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductTierPriceInstance($value)
            ? self::TYPE_MAGENTO_TIER_PRICE
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isProductTierPriceClassName($type)
            ? self::TYPE_MAGENTO_TIER_PRICE
            : null;
    }

    private function isProductTierPriceInstance($value): bool
    {
        return is_object($value) && $value instanceof ProductTierPriceInterface;
    }

    private function isProductTierPriceClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, ProductTierPriceInterface::class);
    }

    /**
     * @param ProductTierPriceInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->isProductTierPriceInstance($value)
            ? $this->formatTierPrice($value)
            : null;
    }

    /**
     * @param ProductTierPriceInterface|mixed $value
     * @param int $maxRecursionDepth
     * @return string|null
     */
    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->isProductTierPriceInstance($value)
            ? $this->implode([
                'Qty'    => $value->getQty(),
                'GroupID'  => $value->getCustomerGroupId(),
                'Amount' => $value->getValue(),
            ])
            : null;
    }

    private function formatTierPrice(ProductTierPriceInterface $value): string
    {
        return $this->implode(['Qty' => $value->getQty(), 'Value' => $value->getValue()]);
    }

    private function implode(array $parts): string
    {
        $keyValues = map(function (string $key, $value): string {
            return sprintf('%s: %s', $key, $value);
        }, keys($parts), $parts);
        return '[' . implode(', ', $keyValues) . ']';
    }
}
