<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;
use Magento\Customer\Api\Data\AddressInterface;

use function array_filter as filter;

class CustomerAddressDataType implements DataTypeInterface
{
    const TYPE_MAGENTO_CUSTOMER_ADDRESS = 'magento_customer_address';

    public function valueToTypeCode($value): ?string
    {
        return $this->isAddressInstance($value)
            ? self::TYPE_MAGENTO_CUSTOMER_ADDRESS
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $this->isAddressClassName($type)
            ? self::TYPE_MAGENTO_CUSTOMER_ADDRESS
            : null;
    }

    private function isAddressInstance($value): bool
    {
        return is_object($value) && $value instanceof AddressInterface;
    }

    private function isAddressClassName($value): bool
    {
        return is_string($value) && is_subclass_of($value, AddressInterface::class);
    }

    /**
     * @param AddressInterface|null $value
     * @return string|null
     */
    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? $this->renderAddress($value)
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }

    private function renderAddress(?AddressInterface $value): string
    {
        $parts = [
            $value->getFirstname() . ' ' . $value->getLastname(),
            implode(', ', filter($value->getStreet() ?? [])),
            $value->getCity(),
            $value->getPostcode(),
            $value->getCountryId(),
        ];
        return implode(', ', filter($parts));
    }
}
