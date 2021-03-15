<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Catalog\Api\Data\CategoryLinkInterface;

class CategoryLinkDataType implements DataTypeInterface
{
    public const TYPE_MAGENTO_CATEGORY_LINK = 'magento_category_link';

    public function valueToTypeCode($value): ?string
    {
        return $this->isCategoryLinkInstance($value)
            ? self::TYPE_MAGENTO_CATEGORY_LINK
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isCategoryLinkClassName($type)
            ? self::TYPE_MAGENTO_CATEGORY_LINK
            : null;
    }

    private function isCategoryLinkInstance($value): bool
    {
        return is_object($value) && $value instanceof CategoryLinkInterface;
    }

    private function isCategoryLinkClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, CategoryLinkInterface::class);
    }

    /**
     * @param CategoryLinkInterface|mixed $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->formatCategoryLink($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function formatCategoryLink(CategoryLinkInterface $categoryLink): string
    {
        return sprintf('cat_%d', $categoryLink->getCategoryId());
    }
}
