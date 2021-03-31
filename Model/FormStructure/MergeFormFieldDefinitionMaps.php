<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface as FieldDefinition;

class MergeFormFieldDefinitionMaps
{
    /**
     * @param FieldDefinition[] $fieldsFromEntity
     * @param FieldDefinition[] $fieldsFromConfig
     * @return FieldDefinition[]
     */
    public function merge(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        $nameToSortOrderMap = $this->buildFieldNameToSortOrderMap($fieldsFromEntity, $fieldsFromConfig);

        $fieldsOnlyInEntity = array_diff_key($fieldsFromEntity, $fieldsFromConfig);
        $fieldsOnlyInConfig = array_diff_key($fieldsFromConfig, $fieldsFromEntity);
        $keysInBoth         = $this->sortByValue($this->fieldKeysInBoth($fieldsFromEntity, $fieldsFromConfig));

        $fieldsInBoth = map(
            [$this, 'mergeFieldDefinition'],
            $this->sortByKey($this->filterFieldsByKey($fieldsFromEntity, $keysInBoth)),
            $this->sortByKey($this->filterFieldsByKey($fieldsFromConfig, $keysInBoth)),
        );
        $fields        = merge($fieldsOnlyInConfig, zip($keysInBoth, $fieldsInBoth), $fieldsOnlyInEntity);

        return $this->sortBySortOrder($fields, $nameToSortOrderMap);
    }

    private function filterFieldsByKey(array $fields, array $keys): array
    {
        return filter($fields, function (FieldDefinition $f) use ($keys) {
            return in_array($f->getName(), $keys);
        });
    }

    private function fieldKeysInBoth(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        return array_intersect(keys($fieldsFromConfig), keys($fieldsFromEntity));
    }

    private function sortByKey(array $array): array
    {
        ksort($array, \SORT_STRING);
        return $array;
    }

    private function sortByValue(array $array): array
    {
        asort($array);
        return $array;
    }

    private function mergeFieldDefinition(FieldDefinition $entityField, FieldDefinition $configField): FieldDefinition
    {
        return $entityField->merge($configField);
    }

    /**
     * @param FieldDefinition[] $fieldsFromEntity
     * @param FieldDefinition[] $fieldsFromConfig
     */
    private function buildFieldNameToSortOrderMap(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        // Find sortOrder for fields from config where missing, starting from the highest declared sortOrder value or 0.
        // Then find sortOrder for all fields that are only found on the entity.
        // The field name to sortOrder map is used to sort the fields array after merging

        $sortOrdersFromConfig = map(function (FieldDefinition $f): ?int {
            return $f->getSortOrder();
        }, $fieldsFromConfig);
        $maxSortOrder         = max(map('intval', filter($sortOrdersFromConfig, function ($v): bool {
            return ! is_null($v); // keep "0" because it is a valid sortOrder value
        })) ?: [0]);

        $nameToSortOrderMapFromConfig = map(function (FieldDefinition $f) use (&$maxSortOrder): int {
            return $f->getSortOrder() ?? ++$maxSortOrder;
        }, $fieldsFromConfig);

        $map = reduce($fieldsFromEntity, function (array $map, FieldDefinition $f) use (&$maxSortOrder): array {
            $map[$f->getName()] = (int) ($map[$f->getName()] ?? $f->getSortOrder() ?? ++$maxSortOrder);
            return $map;
        }, $nameToSortOrderMapFromConfig);

        asort($map);

        return $map;
    }

    /**
     * @param FieldDefinition[] $fields
     * @param int[] $nameToSortOrderMap
     */
    private function sortBySortOrder(array $fields, array $nameToSortOrderMap)
    {
        uasort($fields, function (FieldDefinition $a, FieldDefinition $b) use ($nameToSortOrderMap): int {
            return $nameToSortOrderMap[$a->getName()] <=> $nameToSortOrderMap[$b->getName()];
        });
        return $fields;
    }
}
