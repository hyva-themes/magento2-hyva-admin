<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

use function array_keys as keys;
use function array_merge as merge;
use function array_map as map;

class HyvaFormDefinition implements HyvaFormDefinitionInterface
{
    private string $formName;

    private HyvaFormConfigReaderInterface $formConfigReader;

    private FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory;

    private ?array $memoizedGridConfig;

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
        $includeFields = $this->getFormConfig()['fields']['include'] ?? [];
        return map(function (string $fieldName) use ($includeFields): FormFieldDefinitionInterface {
            return $this->formFieldDefinitionFactory->create(merge([
                'name'     => $fieldName,
                'formName' => $this->getFormName(),
            ], $includeFields[$fieldName]));
        }, keys($includeFields));
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
        if (! isset($this->memoizedGridConfig)) {
            $this->memoizedGridConfig = $this->formConfigReader->getFormConfiguration($this->getFormName());
        }
        return $this->memoizedGridConfig;
    }
}
