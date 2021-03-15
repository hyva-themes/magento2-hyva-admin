<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface as TypeGuesser;
use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;

use function array_reduce as reduce;

class DataTypeFacade implements TypeGuesser, DataTypeToStringConverterLocatorInterface
{
    /**
     * @var array
     */
    private $dataTypeClassMap;

    /**
     * @var DataTypeGuesserFactory
     */
    private $dataTypeGuesserFactory;

    /**
     * @var DataTypeValueToStringConverterFactory
     */
    private $dataTypeValueToStringConverterFactory;

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
            return $type ?? $this->classAndValueToTypeCode($class, $value);
        }, null);
    }

    private function classAndValueToTypeCode(string $class, $value): ?string
    {
        $dataTypeGuesser = $this->dataTypeGuesserFactory->get($class);
        return $dataTypeGuesser
            ? $dataTypeGuesser->valueToTypeCode($value)
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return reduce($this->dataTypeClassMap, function (?string $typeCode, string $class) use ($type): ?string {
            return $typeCode ?? $this->classAndTypeToTypeCode($class, $type);
        }, null);
    }

    private function classAndTypeToTypeCode(string $class, string $type): ?string
    {
        $dataTypeGuesser = $this->dataTypeGuesserFactory->get($class);
        return $dataTypeGuesser
            ? $dataTypeGuesser->typeToTypeCode($type)
            : null;
    }

    public function forTypeCode(string $typeCode): ?DataTypeValueToStringConverterInterface
    {
        return isset($this->dataTypeClassMap[$typeCode])
            ? $this->dataTypeValueToStringConverterFactory->get($this->dataTypeClassMap[$typeCode])
            : null;
    }
}
