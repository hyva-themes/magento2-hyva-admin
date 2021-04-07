<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Exception\UnableToFetchPropertyFromValueException;
use Hyva\Admin\Model\TypeReflection\TypeMethod;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;

use function array_map as map;

class MethodValueBindings
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function resolveAll(array $bindingsConfig): array
    {
        return map([$this, 'resolveBindValue'], $bindingsConfig);
    }

    public function resolveBindValue(array $binding)
    {
        return array_key_exists('value', $binding)
            ? $binding['value']
            : $this->resolveBindMethod($binding);
    }

    private function resolveBindMethod(array $binding)
    {
        $typeAndMethod = $binding['method'] ?? (isset($binding['requestParam']) ? RequestInterface::class . '::getParam' : null);

        [$type, $method] = $this->splitTypeAndMethod($typeAndMethod, $binding['field'] ?? '- not specified -');

        $param = $binding['param'] ?? $binding['requestParam'] ?? null;

        $instance = $this->objectManager->get($type);
        $value    = isset($param) ? $instance->{$method}($param) : $instance->{$method}();

        return ($binding['property'] ?? false) && $binding['property'] !== ''
            ? $this->fetchProperty($value, $binding['property'])
            : $value;
    }

    private function fetchProperty($value, string $property)
    {
        if (is_object($value)) {
            return $this->fetchObjectProperty($property, $value);
        }
        if (is_array($value)) {
            return $value[$property];
        }
        $msg = sprintf('Unable to fetch property "%s" from value of type "%s"', $property, gettype($value));
        throw new UnableToFetchPropertyFromValueException($msg);
    }

    private function fetchObjectProperty(string $property, $value)
    {
        $getter = 'get' . SimpleDataObjectConverter::snakeCaseToCamelCase($property);
        if (method_exists($value, $getter)) {
            return $value->{$getter}();
        }
        if (method_exists($value, 'getData')) {
            return $value->getData($property);
        }
        if ($value instanceof \ArrayAccess) {
            return $value[$property];
        }
        if (property_exists($value, $property)) {
            return $value->{$property};
        }
        $msg = sprintf('Unable to fetch property "%s" from an instance of "%s"', $property, get_class($value));
        throw new UnableToFetchPropertyFromValueException($msg);
    }

    private function splitTypeAndMethod(?string $typeAndMethod, string $field): array
    {
        try {
            return TypeMethod::split($typeAndMethod);
        } catch (\RuntimeException $exception) {
            $msg = sprintf('Invalid method value binding "%s" specified: method="%s"', $field, $typeAndMethod);
            throw new \RuntimeException($msg, ['exception' => $exception]);
        }
    }
}
