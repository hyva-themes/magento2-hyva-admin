<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

class DataTypeToStringConverterLocator
{
    /**
     * @var string[]
     */
    private array $valueToStringConverters;

    /**
     * @var DataTypeValueToStringConverterFactory
     */
    private DataTypeValueToStringConverterFactory $dataTypeValueToStringConverterFactory;

    public function __construct(
        array $valueToStringConverterClasses,
        DataTypeValueToStringConverterFactory $dataTypeValueToStringConverterFactory
    ) {
        $this->valueToStringConverters               = $valueToStringConverterClasses;
        $this->dataTypeValueToStringConverterFactory = $dataTypeValueToStringConverterFactory;
    }

    public function forTypeCode(string $typeCode): ?DataTypeValueToStringConverterInterface
    {
        return isset($this->valueToStringConverters[$typeCode])
            ? $this->dataTypeValueToStringConverterFactory->get($this->valueToStringConverters[$typeCode])
            : null;
    }
}
