<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

class DateTimeDataType implements DataTypeInterface
{
    const TYPE_DATETIME = 'datetime';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface
     */
    private $dateTimeFormatter;

    public function __construct(DateTimeFormatterInterface $dateTimeFormatter)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    public function valueToTypeCode($value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d\d-\d\d[ T]\d\d:\d\d:\d\d$/', $value)
            ? self::TYPE_DATETIME
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_DATETIME ? self::TYPE_DATETIME : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->dateTimeFormatter->formatObject(new \DateTimeImmutable($value))
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
