<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\TypeReflection\MethodsMap;

use function array_column as pick;
use function array_keys as keys;
use function array_filter as filter;

class FormSource
{
    private string $formName;

    private array $loadConfig;

    private array $saveConfig;

    private MethodsMap $methodsMap;

    public function __construct(string $formName, array $loadConfig, array $saveConfig, MethodsMap $methodsMap)
    {
        $this->formName   = $formName;
        $this->loadConfig = $loadConfig;
        $this->saveConfig = $saveConfig;
        $this->methodsMap = $methodsMap;
    }

    public function getLoadMethod(): string
    {
        if (!$this->loadConfig['method']) {
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
         * 1. Use explicitly specified type attribute if present
         * 2. Use reflection on the load method return type
         * 3. Check if a type can be determined from the save configuration
         * 4. Default to 'array' type
         */
        return $this->loadConfig['type']
            ?? $this->getReturnType($this->getLoadMethod(), 'load')
            ?? $this->getSaveParameterType()
            ?? $this->getReturnType($this->getSaveMethod(), 'save')
            ?? 'array';
    }

    public function getSaveMethod(): string
    {
        if (!$this->saveConfig['method']) {
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
         * 1. Use explicitly specified type attribute if present
         * 2. Use reflection on save method used to find the type of the formData parameter of first argument
         * 3. Use reflection on the save method return type
         * 4. Use type attribute on the load element if present
         * 5. Use reflection on the load method return type
         * 6. Default to 'array' type
         */
        return $this->saveConfig['type']
            ?? $this->getSaveParameterType()
            ?? $this->getReturnType($this->getSaveMethod(), 'save')
            ?? $this->loadConfig['type']
            ?? $this->getReturnType($this->getLoadMethod(), 'load')
            ?? 'array';
    }

    private function getReturnType(string $typeAndMethod, string $methodPurpose): ?string
    {
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);
        $methodsReturnTypeMap = $this->methodsMap->getMethodsMap($type);

        return $methodsReturnTypeMap[$method] ?? null;
    }

    private function splitTypeAndMethod(?string $typeAndMethod, string $methodPurpose): array
    {
        if (!$typeAndMethod || !preg_match('/^.+::.+$/', $typeAndMethod)) {
            $msg = sprintf(
                'Invalid form "%s" type specified on form "%s": method="%s"',
                $methodPurpose,
                $this->formName,
                $typeAndMethod
            );
            throw new \RuntimeException($msg);
        }

        return explode('::', $typeAndMethod);
    }

    private function getSaveFormDataArgument(): ?string
    {
        $isFormDataArgument    = fn(array $arg): bool => ($arg['formData'] ?? false) === 'true';
        $formDataArguments     = filter($this->getSaveBindArguments(), $isFormDataArgument);
        $formDataArgumentNames = keys($formDataArguments);
        if (count($formDataArguments) > 1) {
            $msg = sprintf(
                'Error on form "%s": only one formData save argument allowed but found: %s',
                $this->formName,
                implode(', ', $formDataArgumentNames)
            );
            throw new \RuntimeException($msg);
        }

        return $formDataArgumentNames[0] ?? $this->getFirstParameterName($this->getSaveMethod(), 'save') ?? null;
    }

    private function getFirstParameterName(string $typeAndMethod, string $methodPurpose): ?string
    {
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);
        $methodParameters = keys($this->methodsMap->getRealMethodParameters($type, $method));

        return $methodParameters[0] ?? null;
    }

    private function getSaveParameterType(): ?string
    {
        $saveFormDataArgumentName = $this->getSaveFormDataArgument();
        return $saveFormDataArgumentName
            ? $this->getParameterType($this->getSaveMethod(), $saveFormDataArgumentName, 'save')
            : null;
    }

    private function getParameterType(string $typeAndMethod, string $parameterName, string $methodPurpose): ?string
    {
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);

        return $this->methodsMap->getParameterType($type, $method, $parameterName);
    }
}
