<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\FormEntity\FormLoadEntityRepository;
use Hyva\Admin\Model\TypeReflection\MethodsMap;

use Hyva\Admin\Model\TypeReflection\TypeMethod;
use function array_filter as filter;
use function array_keys as keys;

class FormSource
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var array
     */
    private $loadConfig;

    /**
     * @var array
     */
    private $saveConfig;

    /**
     * @var MethodsMap
     */
    private $methodsMap;

    public function __construct(
        string $formName,
        array $loadConfig,
        array $saveConfig,
        MethodsMap $methodsMap
    ) {
        $this->formName   = $formName;
        $this->loadConfig = $loadConfig;
        $this->saveConfig = $saveConfig;
        $this->methodsMap = $methodsMap;
    }

    public function getLoadMethodName(): string
    {
        $this->validateMethodExists($this->loadConfig['method'] ?? null, 'load');

        return $this->loadConfig['method'];
    }

    public function getLoadBindArgumentConfig(): array
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
            ?? $this->getReturnType($this->getLoadMethodName(), 'load')
            ?? $this->saveConfig['type']
            ?? $this->getSaveParameterType()
            ?? $this->getReturnType($this->getSaveMethodName(), 'save')
            ?? 'array';
    }

    public function getSaveMethodName(): string
    {
        $this->validateMethodExists($this->saveConfig['method'] ?? null, 'save');

        return $this->saveConfig['method'];
    }

    public function getSaveBindArgumentConfig(): array
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
            ?? $this->getReturnType($this->getSaveMethodName(), 'save')
            ?? $this->loadConfig['type']
            ?? $this->getReturnType($this->getLoadMethodName(), 'load')
            ?? 'array';
    }

    private function getReturnType(string $typeAndMethod, string $methodPurpose): ?string
    {
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);
        $methodsReturnTypeMap = $this->methodsMap->getMethodsReturnTypeMap($type);

        $returnType = $methodsReturnTypeMap[$method] ?? null;

        // Return null instead of mixed because of better ?? chaining capabilities.
        return $returnType !== 'mixed' ? $returnType : null;
    }

    private function splitTypeAndMethod(?string $typeAndMethod, string $methodPurpose): array
    {
        try {
            return TypeMethod::split($typeAndMethod);
        } catch (\RuntimeException $exception) {
            $msg = sprintf(
                'Invalid form "%s" type specified on form "%s": method="%s", Type::method syntax required',
                $methodPurpose,
                $this->formName,
                $typeAndMethod
            );
            throw new \RuntimeException($msg, ['exception' => $exception]);
        }
    }

    private function getSaveFormDataArgument(): ?string
    {
        $isFormDataArgument    = function(array $arg): bool { return ($arg['formData'] ?? false) === 'true'; };
        $formDataArguments     = filter($this->getSaveBindArgumentConfig(), $isFormDataArgument);
        $formDataArgumentNames = keys($formDataArguments);
        // TODO: ignore optional arguments, see \Hyva\Admin\Model\TypeReflection\MethodsMap::isMethodValidSetter
        if (count($formDataArguments) > 1) {
            $msg = sprintf(
                'Error on form "%s": only one formData save argument allowed but found: %s',
                $this->formName,
                implode(', ', $formDataArgumentNames)
            );
            throw new \RuntimeException($msg);
        }

        return $formDataArgumentNames[0] ?? $this->getFirstParameterName($this->getSaveMethodName(), 'save') ?? null;
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
            ? $this->getParameterType($this->getSaveMethodName(), $saveFormDataArgumentName, 'save')
            : null;
    }

    private function getParameterType(string $typeAndMethod, string $parameterName, string $methodPurpose): ?string
    {
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);

        return $this->methodsMap->getParameterType($type, $method, $parameterName);
    }

    private function validateMethodExists(?string $typeAndMethod, string $methodPurpose): void
    {

        if (!$typeAndMethod) {
            throw new \RuntimeException(sprintf(
                'No %s method specified on form "%s"',
                $methodPurpose,
                $this->formName));
        }
        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $methodPurpose);
        if (!method_exists($type, $method)) {
            throw new \RuntimeException(sprintf(
                '%s method "%s" for form "%s" not found',
                ucfirst($methodPurpose),
                $typeAndMethod,
                $this->formName
            ));
        }
    }
}
