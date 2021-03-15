<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;

class ProductCustomOptionDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_PRODUCT_CUSTOM_OPTION = 'magento_product_custom_option';

    /**
     * @var DataTypeToStringConverterLocatorInterface
     */
    private $dataTypeToStringConverterLocator;

    public function __construct(DataTypeToStringConverterLocatorInterface $dataTypeToStringConverterLocator)
    {
        $this->dataTypeToStringConverterLocator = $dataTypeToStringConverterLocator;
    }

    public function valueToTypeCode($value): ?string
    {
        return $this->isProductOptionInstance($value)
            ? self::TYPE_MAGENTO_PRODUCT_CUSTOM_OPTION
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isProductOptionClassName($type)
            ? self::TYPE_MAGENTO_PRODUCT_CUSTOM_OPTION
            : null;
    }

    private function isProductOptionInstance($value): bool
    {
        return is_object($value) && $value instanceof ProductCustomOptionInterface;
    }

    private function isProductOptionClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, ProductCustomOptionInterface::class);
    }

    /**
     * @param ProductCustomOptionInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatCustomOption($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatCustomOptionAsArray($value, $maxRecursionDepth)
            : '';
    }

    private function formatCustomOptionAsArray(ProductCustomOptionInterface $value, $maxRecursionDepth): string
    {
        $parts = [
            $value->getTitle(),
            $value->getType(),
            '(' . ($value->getIsRequire() ? 'required' : 'optional') . ')',
        ];
        $converter = $this->dataTypeToStringConverterLocator->forTypeCode('array');
        return $converter
            ? 'Custom Option: ' . $converter->toHtmlRecursive($parts, $maxRecursionDepth)
            : '';
    }

    private function formatCustomOption(ProductCustomOptionInterface $value): string
    {
        return sprintf('Custom Option "%s"', $value->getTitle());
    }
}
