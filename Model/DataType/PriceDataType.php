<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * This data type can not be automatically determined, it must be configured as the column type in the grid
 */
class PriceDataType implements DataTypeValueToStringConverterInterface
{
    public const TYPE_PRICE = 'price';

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(PriceCurrencyInterface $priceCurrency)
    {
        $this->priceCurrency = $priceCurrency;
    }

    public function toString($value): ?string
    {
        return is_scalar($value)
            ? $this->priceCurrency->format($value, false)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
