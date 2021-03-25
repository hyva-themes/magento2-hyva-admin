<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;

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
        $fieldsOnlyInEntity = array_diff_key($fieldsFromEntity, $fieldsFromConfig);
        $fieldsOnlyInConfig = array_diff_key($fieldsFromConfig, $fieldsFromEntity);
        $keysInBoth         = $this->fieldKeysInBoth($fieldsFromEntity, $fieldsFromConfig);

        $fieldsInBoth = map(
            [$this, 'mergeFieldDefinition'],
            $this->filterFieldsByKey($fieldsFromEntity, $keysInBoth),
            $this->filterFieldsByKey($fieldsFromConfig, $keysInBoth),
        );
        return merge($fieldsOnlyInConfig, zip($keysInBoth, $fieldsInBoth), $fieldsOnlyInEntity);
    }

    private function filterFieldsByKey(array $fields, array $keys): array
    {
        return $this->sortFieldsMap(filter($fields, function (FieldDefinition $f) use ($keys) {
            return in_array($f->getName(), $keys);
        }));
    }

    private function fieldKeysInBoth(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        $keysInBoth = array_intersect(keys($fieldsFromEntity), keys($fieldsFromConfig));
        sort($keysInBoth, \SORT_STRING);

        return $keysInBoth;
    }

    private function sortFieldsMap(array $fieldDefinitions): array
    {
        ksort($fieldDefinitions, \SORT_STRING);
        return $fieldDefinitions;
    }

    private function mergeFieldDefinition(FieldDefinition $entityField, FieldDefinition $configField): FieldDefinition
    {
        return $entityField->merge($configField);
    }
}
