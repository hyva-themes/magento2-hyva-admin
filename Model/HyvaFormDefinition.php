<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_merge as merge;
use function array_map as map;
use function array_reduce as reduce;
use function array_values as values;

class HyvaFormDefinition implements HyvaFormDefinitionInterface
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var HyvaFormConfigReaderInterface
     */
    private $formConfigReader;

    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var array|null
     */
    private $memoizedGridConfig;

    public function __construct(
        string $formName,
        HyvaFormConfigReaderInterface $formConfigReader,
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory
    ) {
        $this->formName                   = $formName;
        $this->formConfigReader           = $formConfigReader;
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getLoadConfig(): array
    {
        return $this->getFormConfig()['load'] ?? [];
    }

    public function getSaveConfig(): array
    {
        return $this->getFormConfig()['save'] ?? [];
    }

    public function getFieldDefinitions(): array
    {
        $fieldCodes = keys($this->getIncludeFieldsConfig());
        $fields     = map(function (string $fieldName): FormFieldDefinitionInterface {
            return $this->formFieldDefinitionFactory->create(merge([
                'name'       => $fieldName,
                'formName'   => $this->getFormName(),
                'isExcluded' => in_array($fieldName, $this->getExcludeFieldsConfig(), true),
            ], $this->getIncludeFieldsConfig()[$fieldName]));
        }, $fieldCodes);
        return zip($fieldCodes, $fields);
    }

    public function getSectionsConfig(): array
    {
        return $this->getFormConfig()['sections'] ?? [];
    }

    public function getNavigationConfig(): array
    {
        return $this->getFormConfig()['navigation'] ?? [];
    }

    private function getFormConfig(): array
    {
        if (!isset($this->memoizedGridConfig)) {
            $this->memoizedGridConfig = $this->formConfigReader->getFormConfiguration($this->getFormName());
        }
        return $this->memoizedGridConfig;
    }

    private function getExcludeFieldsConfig(): array
    {
        return $this->getFormConfig()['fields']['exclude'] ?? [];
    }

    private function getIncludeFieldsConfig(): array
    {
        return $this->getFormConfig()['fields']['include'] ?? [];
    }

    private function frequencies(array $a): array
    {
        return reduce($a, function (array $f, $x): array {
            $f[$x] = ($f[$x] ?? 0) + 1;
            return $f;
        }, []);
    }

    public function getGroupsFromSections(): array
    {
        $sectionConfig = $this->getSectionsConfig();
        $groupsConfig  = values(filter(merge([], ...map(function (array $s): array {
            return $s['groups'] ?? [];
        }, $sectionConfig))));

        $this->validateGroupIdsAreUniquePerSection($groupsConfig);

        return $groupsConfig;
    }

    private function validateGroupIdsAreUniquePerSection(array $groupsConfig): void
    {
        $groupIds     = map(function (array $groupConfig): string {
            return $groupConfig['id'];
        }, $groupsConfig);
        $dupeGroupIds = keys(filter($this->frequencies($groupIds), function(int $n): bool { return $n > 1; }));
        if (count($dupeGroupIds) > 0) {
            $this->throwGroupIdInMultipleSectionsException($dupeGroupIds);
        }
    }

    private function throwGroupIdInMultipleSectionsException(array $dupeGroupIds): void
    {
        $idPaths = reduce($this->getSectionsConfig(), function (array $acc, array $section) use ($dupeGroupIds): array {
            $sectionGroups    = $section['groups'] ?? [];
            $dupesInSection   = filter($sectionGroups, function (array $group) use ($dupeGroupIds): bool {
                return in_array($group['id'], $dupeGroupIds);
            });
            $dupeGroupIdPaths = map(function (array $group) use ($section): string {
                return $section['id'] . '/' . $group['id'];
            }, $dupesInSection);
            return merge($acc, $dupeGroupIdPaths);
        }, []);
        throw new \RuntimeException(
            'The same section group ID(s) must not be used in multiple sections, found: ' . implode(', ', $idPaths)
        );
    }
}
