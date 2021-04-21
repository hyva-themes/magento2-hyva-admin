<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\System\Store as SystemStore;

use function array_map as map;

class WebsiteIdDataType implements DataTypeValueToStringConverterInterface
{
    public const TYPE_STORE_ID = 'website_id';

    /**
     * @var SystemStore
     */
    private $systemStore;

    public function __construct(SystemStore $systemStore)
    {
        $this->systemStore = $systemStore;
    }

    public function toString($value): ?string
    {
        $websiteIds = is_array($value) ? $value : [$value];

        return in_array(0, $websiteIds) && count($websiteIds) === 1
            ? $this->getAllWebsitesLabel()
            : implode(', ', map(function ($websiteId): string {
                return $this->systemStore->getWebsiteName($websiteId);
            }, $websiteIds));
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function getAllWebsitesLabel(): string
    {
        return (string) __('All Websites');
    }
}
