<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

// TODO: find a better name...
class FormInputOutput
{
    private string $formName;

    private array $loadConfig;

    private array $saveConfig;

    public function __construct(string $formName, array $loadConfig, array $saveConfig)
    {
        $this->formName   = $formName;
        $this->loadConfig = $loadConfig;
        $this->saveConfig = $saveConfig;
    }

    public function getLoadMethod(): string
    {
        if (! $this->loadConfig['method']) {
            throw new \RuntimeException(sprintf('No load method specified on form "%s"', $this->formName));
        }
        return $this->loadConfig['method'];
    }

    public function getLoadBindArguments(): array
    {
        return $this->loadConfig['bindArguments'] ?? [];
    }

    public function getLoadType(): string
    {
/*
1. An entity attribute is specified on the load attribute,
2. Reflection on the load method will be used to determine the entity type.
2.5 Maybe, in future, check if a type can be determined from the save configuration (without the steps 3 and 4).
3. The load type 'array' will be used as a default.
 */
    }

    public function getSaveMethod(): string
    {
        if (! $this->saveConfig['method']) {
            throw new \RuntimeException(sprintf('No save method specified on form "%s"', $this->formName));
        }
        return $this->saveConfig['method'];
    }

    public function getSaveBindArguments(): array
    {
        return $this->saveConfig['bindArguments'] ?? [];
    }

    public function getSaveType(): string
    {
/*
1. If an entity attribute is present on the save node, it will be used
2. Otherwise reflection is used to find the type of the first argument of the save method.
3. If still no target type is found, but an entity type is present on the load element, it will be used.
4. Finally reflection is used on the load method return type to determine the target type.
5. If no type information is found or if the type is array, the PHP array is used
 */
    }
}
