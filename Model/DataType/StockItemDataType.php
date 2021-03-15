<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Reflection\DataObjectProcessor;

class StockItemDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_STOCK_ITEM = 'magento_stock_item';

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var DataTypeToStringConverterLocatorInterface
     */
    private $toStringConverterLocator;

    public function __construct(
        DataObjectProcessor $dataObjectProcessor,
        DataTypeToStringConverterLocatorInterface $toStringConverterLocator
    ) {
        $this->dataObjectProcessor      = $dataObjectProcessor;
        $this->toStringConverterLocator = $toStringConverterLocator;
    }

    public function valueToTypeCode($value): ?string
    {
        return $this->isStockItemInstance($value)
            ? self::TYPE_MAGENTO_STOCK_ITEM
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isStockItemClassName($type)
            ? self::TYPE_MAGENTO_STOCK_ITEM
            : null;

    }

    private function isStockItemInstance($value): bool
    {
        return is_object($value) && $value instanceof StockItemInterface;
    }

    private function isStockItemClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, StockItemInterface::class);
    }

    /**
     * @param StockItemInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatStockItem($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->toStringConverterLocator->forTypeCode('array')->toHtmlRecursive(
                $this->dataObjectProcessor->buildOutputDataArray($value, StockItemInterface::class),
                $maxRecursionDepth
            )
            : null;
    }

    private function formatStockItem(StockItemInterface $stockItem): string
    {
        return sprintf('Stock: %s', $stockItem->getQty());
    }
}
