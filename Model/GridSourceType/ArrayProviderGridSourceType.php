<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use function array_keys as keys;
use function array_values as values;

class ArrayProviderGridSourceType implements GridSourceTypeInterface
{
    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private ArrayProviderSourceType\ArrayProviderFactory $arrayProviderFactory;

    private RawGridSourceContainer $memoizedGridData;

    private string $arrayProviderClass;

    private string $gridName;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ArrayProviderSourceType\ArrayProviderFactory $arrayProviderFactory,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory
    ) {
        $this->validateArrayProviderConfiguration($gridName, $sourceConfiguration);

        $this->gridName                = $gridName;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->arrayProviderFactory    = $arrayProviderFactory;
        $this->arrayProviderClass      = $sourceConfiguration['arrayProvider'] ?? '';
        $this->columnDefinitionFactory = $columnDefinitionFactory;
    }

    private function validateArrayProviderConfiguration(string $gridName, array $sourceConfiguration): void
    {
        $providerClass = $sourceConfiguration['arrayProvider'] ?? '';

        if (!$providerClass) {
            $msg1 = sprintf('No array provider class specified to array provider for grid "%s"', $gridName);
            throw new \InvalidArgumentException($msg1);
        }
        // No check if the provider class exists happens here so virtual types can be used.
    }

    public function getColumnKeys(): array
    {
        return keys($this->getFirstRow());
    }

    public function extractValue($record, string $key)
    {
        if (!array_key_exists($key, $record)) {
            throw new \RuntimeException(sprintf('No column value "%s" on grid row.', $key));
        }
        return $record[$key];
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $firstRecord = $this->getFirstRow()[$key] ?? null;
        return $this->columnDefinitionFactory->create([
            'key'  => $key,
            'type' => $this->determineType($firstRecord),
        ]);
    }

    /**
     * Other source types should not keep a reference to the grid data.
     *
     * The array grid source type is special and keeps a reference, because it needs to access
     * the first record in order to do column type reflection.
     * Other grid source types should use reflection on the data type classes or the query columns instead.
     * These should not keep a reference to the data so iterators can be used and GC can collect values that are
     * no longer needed.
     *
     * @return RawGridSourceContainer
     */
    public function fetchData(): RawGridSourceContainer
    {
        if (!isset($this->memoizedGridData)) {
            $provider               = $this->arrayProviderFactory->create($this->arrayProviderClass);
            $this->memoizedGridData = RawGridSourceContainer::forData($provider->getArray());
        }

        return $this->memoizedGridData;
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData);
    }

    private function getFirstRow(): array
    {
        return values($this->gridSourceDataAccessor->unbox($this->fetchData()))[0] ?? [];
    }

    /**
     * @param mixed $record
     * @return string
     */
    private function determineType($record): string
    {
        if (is_string($record)) {
            return 'string';
        }
        if (is_int($record)) {
            return 'int';
        }
        if (is_bool($record)) {
            return 'bool';
        }
        if (is_float($record)) {
            return 'float';
        }
        if (is_null($record)) {
            return 'null';
        }
        if (is_object($record)) {
            return sprintf('object<%s>', get_class($record));
        }
        if (is_array($record)) {
            return 'array';
        }
        return 'unknown';
    }
}
