<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\HyvaFormConfigReaderInterface;

class HyvaFormDefinition implements HyvaFormDefinitionInterface
{
    private string $formName;

    private HyvaFormConfigReaderInterface $formConfigReader;

    public function __construct(string $formName, HyvaFormConfigReaderInterface $formConfigReader)
    {
        $this->formName = $formName;
        $this->formConfigReader = $formConfigReader;
    }

    public function getName(): string
    {

    }

    public function getLoadConfig(): array
    {

    }

    public function getSaveConfig(): array
    {

    }

    public function getFieldDefinitions(): array
    {

    }

    public function getSectionsConfig(): array
    {

    }

    public function getNavigationConfig(): array
    {
        return [];
    }
}
