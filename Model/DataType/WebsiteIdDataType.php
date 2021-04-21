<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\System\Store as SystemStore;

use function array_map as map;

class StoreIdDataType implements DataTypeValueToStringConverterInterface
{
    public const TYPE_STORE_ID = 'store_id';

    /**
     * @var SystemStore
     */
    private $systemStore;

    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(SystemStore $systemStore, Escaper $escaper)
    {
        $this->systemStore = $systemStore;
        $this->escaper     = $escaper;
    }

    public function toString($value): ?string
    {
        $storeIds = is_array($value) ? $value : [$value];

        return in_array(0, $storeIds) && count($storeIds) === 1
            ? $this->getAllStoreViewsLabel()
            : implode(', ', map(function ($storeId): string {
                return $this->systemStore->getStoreNamePath($storeId);
            }, $storeIds));
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        $storeIds = is_array($value) ? $value : [$value];

        return in_array(0, $storeIds) && count($storeIds) === 1
            ? $this->getAllStoreViewsLabel()
            : $this->formatHierarchy($this->systemStore->getStoresStructure(/* isAll: */ false, $storeIds));
    }

    private function formatHierarchy(array $level, int $depth = 0): string
    {
        return implode('<br/>', map(function (array $child) use ($depth): string {
            $next = '<br/>' . $this->formatHierarchy($child['children'] ?? [], $depth + 1);
            return $this->indentLabel(3 * $depth, $child['label']) . $next;
        }, $level));
    }

    private function indentLabel(int $n, string $label): string
    {
        return str_repeat('&nbsp;', $n) . $this->escaper->escapeHtml($label);
    }

    private function getAllStoreViewsLabel(): string
    {
        return (string) __('All Store Views');
    }
}
