<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface as TypeGuesser;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

use function array_reduce as reduce;

class DataTypeFacade implements TypeGuesser, DataTypeToStringConverterLocatorInterface
{
    private array $dataTypeClassMap;

    private DataTypeGuesserFactory $dataTypeGuesserFactory;

    private DataTypeValueToStringConverterFactory $dataTypeValueToStringConverterFactory;

    public function __construct(
        array $dataTypeClassMap,
        DataTypeGuesserFactory $dataTypeGuesserFactory,
        DataTypeValueToStringConverterFactory $dataTypeValueToStringConverterFactory
    ) {
        $this->dataTypeClassMap                      = $dataTypeClassMap;
        $this->dataTypeGuesserFactory                = $dataTypeGuesserFactory;
        $this->dataTypeValueToStringConverterFactory = $dataTypeValueToStringConverterFactory;
    }

    public function valueToTypeCode($value): ?string
    {
        return reduce($this->dataTypeClassMap, function (?string $type, string $class) use ($value): ?string {
            return $type ?? $this->dataTypeGuesserFactory->get($class)->valueToTypeCode($value);
        }, null);
    }

    public function typeToTypeCode(string $type): ?string
    {
        return reduce($this->dataTypeClassMap, function (?string $typeCode, string $class) use ($type): ?string {
            return $typeCode ?? $this->dataTypeGuesserFactory->get($class)->typeToTypeCode($type);
        }, null);
    }

    public function forTypeCode(string $typeCode): ?DataTypeValueToStringConverterInterface
    {
        return isset($this->dataTypeClassMap[$typeCode])
            ? $this->dataTypeValueToStringConverterFactory->get($this->dataTypeClassMap[$typeCode])
            : null;
    }
}
