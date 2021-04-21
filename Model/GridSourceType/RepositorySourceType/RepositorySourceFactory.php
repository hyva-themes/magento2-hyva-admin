<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\MethodsMap;

class RepositorySourceFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var MethodsMap
     */
    private $reflectionMethodsMap;

    public function __construct(ObjectManagerInterface $objectManager, MethodsMap $reflectionMethodsMap)
    {
        $this->objectManager        = $objectManager;
        $this->reflectionMethodsMap = $reflectionMethodsMap;
    }

    private function getSourceRepoClassAndMethod(string $config): array
    {
        $parts = explode('::', $config);
        if (count($parts) !== 2) {
            $msg = sprintf('Invalid repository source configuration: "%s" (expected class::getList)', $config);
            throw new \LogicException($msg);
        }
        return $parts;
    }

    private function getSourceRepoClass(string $config): string
    {
        return $this->getSourceRepoClassAndMethod($config)[0];
    }

    private function getSourceRepoMethod(string $config): string
    {
        return $this->getSourceRepoClassAndMethod($config)[1];
    }

    public function getRepositoryEntityType(string $sourceConfig): string
    {
        $this->validateSourceConfig($sourceConfig);
        $class  = $this->getSourceRepoClass($sourceConfig);
        $method = $this->getSourceRepoMethod($sourceConfig);
        $resultType      = $this->reflectionMethodsMap->getMethodReturnType($class, $method);
        $resultItemsType = $this->reflectionMethodsMap->getMethodReturnType($resultType, 'getItems');

        return substr($resultItemsType, -2) === '[]'
            ? substr($resultItemsType, 0, -2)
            : $resultItemsType;
    }

    public function create(string $sourceConfig): RepositoryGetListInterface
    {
        $this->validateSourceConfig($sourceConfig);
        $class  = $this->getSourceRepoClass($sourceConfig);
        $method = $this->getSourceRepoMethod($sourceConfig);
        $repo   = $this->objectManager->create($class);

        return new class($repo, $method) implements RepositoryGetListInterface {
            private $repo;

            private $method;

            public function __construct($repo, string $method)
            {
                $this->repo   = $repo;
                $this->method = $method;
            }

            public function __invoke(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
            {
                return $this->repo->{$this->method}($searchCriteria);
            }

            public function peek()
            {
                return $this->repo;
            }
        };
    }

    private function validateSourceConfig(string $sourceConfig): void
    {
        $this->validateExists($sourceConfig);
        $this->validateInputType($sourceConfig);
        $this->validateReturnType($sourceConfig);
    }

    private function validateExists(string $sourceConfig): void
    {
        $class = $this->getSourceRepoClass($sourceConfig);
        if (! interface_exists($class) && !class_exists($class)) {
            throw new \LogicException(sprintf('Repository source class "%s" not found', $class));
        }
        $method = $this->getSourceRepoMethod($sourceConfig);
        if (!method_exists($class, $method)) {
            throw new \LogicException(sprintf('Method "%s" not found on repository source "%s"', $method, $class));
        }
    }

    private function validateInputType(string $sourceConfig): void
    {
        $class  = $this->getSourceRepoClass($sourceConfig);
        $method = $this->getSourceRepoMethod($sourceConfig);

        $params = $this->reflectionMethodsMap->getMethodParams($class, $method);
        if (count($params) !== 1) {
            $msg = sprintf('%s requires two arguments, but grid source methods can only have one.', $sourceConfig);
            throw new \LogicException($msg);
        }
        $criteriaInterface = ltrim(SearchCriteriaInterface::class, '\\');
        if ($params[0]['type'] !== $criteriaInterface && !is_subclass_of($params[0]['type'], $criteriaInterface)) {
            $msg = sprintf('The repository source "%s" does not take a %s argument', $sourceConfig, $criteriaInterface);
            throw new \LogicException($msg);
        }
    }

    private function validateReturnType(string $sourceConfig): void
    {
        $class  = $this->getSourceRepoClass($sourceConfig);
        $method = $this->getSourceRepoMethod($sourceConfig);

        $returnType      = $this->reflectionMethodsMap->getMethodReturnType($class, $method);
        $resultInterface = SearchResultsInterface::class;
        if (!is_subclass_of($returnType, $resultInterface)) {
            $msg = sprintf('The repository source "%s" does not return a %s instance', $sourceConfig, $resultInterface);
            throw new \LogicException($msg);
        }
    }
}
