<?php declare(strict_types=1);

namespace Hyva\Admin\Controller\Adminhtml\Form;

use Hyva\Admin\Model\FormEntity\FormFieldValueProcessorInterface;
use Hyva\Admin\Model\HyvaFormDefinitionInterfaceFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    private \Magento\Framework\ObjectManagerInterface $om;
    private LoggerInterface $logger;
    private HyvaFormDefinitionInterfaceFactory $hyvaFormFactory;
    private DataObjectHelper $dataObjectHelper;

    public function __construct(
        Context $context,
        HyvaFormDefinitionInterfaceFactory $hyvaFormFactory,
        DataObjectHelper $dataObjectHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->om = \Magento\Framework\App\ObjectManager::getInstance();
        $this->hyvaFormFactory = $hyvaFormFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger = $logger;
    }

    public function execute(): mixed
    {
        $isAjax = $this->isAjaxRequest();

        try {
            $formName = (string) $this->getRequest()->getParam('form_name');
            $formData = $this->getRequest()->getPostValue();

            if (empty($formData)) {
                throw new \RuntimeException('No form data received');
            }

            $formDefinition = $this->hyvaFormFactory->create(['formName' => $formName]);
            $saveConfig = $formDefinition->getSaveConfig();

            if (empty($saveConfig)) {
                throw new \RuntimeException(sprintf('No save method configured for form: %s', $formName));
            }

            $filteredData = $this->filterFormData($formData);
            $processedData = $this->applyValueProcessors($filteredData, $formDefinition);

            $this->executeSaveMethod($saveConfig, $processedData);

            if ($isAjax) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                    ->setData(['success' => true, 'message' => __('Data saved successfully.')]);
            }

            $this->messageManager->addSuccessMessage(__('Data saved successfully.'));

        } catch (\Exception $e) {
            $this->logger->error('Form save failed', [
                'form_name' => $this->getRequest()->getParam('form_name', 'unknown'),
                'error' => $e->getMessage(),
            ]);

            if ($isAjax) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                    ->setData(['success' => false, 'message' => __('Error saving form: %1', $e->getMessage())]);
            }

            $this->messageManager->addErrorMessage(__('Error saving form: %1', $e->getMessage()));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $referrer = $this->getRequest()->getServer('HTTP_REFERER');

        return $referrer
            ? $resultRedirect->setUrl($referrer)
            : $resultRedirect->setPath('*/*/');
    }

    // -------------------------------------------------------------------------
    // Value processors
    // -------------------------------------------------------------------------

	/**
	* For each field declared in the XML with a valueProcessor,
	* calls fromFieldValue() on the POST value.
	* It is the valueProcessor's responsibility to handle business transformations (type casting, key renaming, etc.).
	*/
    private function applyValueProcessors(array $data, $formDefinition): array
    {
        foreach ($formDefinition->getFieldDefinitions() as $fieldName => $field) {
            $fieldArray = $field->toArray();
            $processorClass = $fieldArray['valueProcessor'] ?? null;

            if (!$processorClass || !isset($data[$fieldName])) {
                continue;
            }

            /** @var FormFieldValueProcessorInterface $processor */
            $processor = $this->om->get($processorClass);
            $data[$fieldName] = $processor->fromFieldValue($data[$fieldName]);
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Save execution
    // -------------------------------------------------------------------------

    private function executeSaveMethod(array $saveConfig, array $entityData): void
    {
        $method = $saveConfig['method'] ?? null;

        if (!$method || strpos($method, '::') === false) {
            throw new \RuntimeException(sprintf('Invalid or missing save method: %s', $method));
        }

        [$className, $methodName] = explode('::', $method, 2);
        $saveInstance = $this->om->create($className);

        if (!method_exists($saveInstance, $methodName)) {
            throw new \RuntimeException(sprintf('Method %s::%s() does not exist', $className, $methodName));
        }

        $paramTypes = $this->getMethodParamTypes($className, $methodName);
        $bindArguments = $saveConfig['bindArguments'] ?? [];
        $callArgs = [];

        foreach ($bindArguments as $argName => $argConfig) {
            if (isset($argConfig['formData']) && $argConfig['formData'] === 'true') {
                $expectedType = $paramTypes[$argName] ?? null;

                if (!$expectedType) {
                    $availableParams = array_keys($paramTypes);
                    throw new \RuntimeException(sprintf(
                        'Cannot resolve type for argument "%s" on %s::%s(). '
                        . 'Available parameters: %s',
                        $argName,
                        $className,
                        $methodName,
                        implode(', ', $availableParams) // ← affiche les vrais noms
                    ));
                }

                $callArgs[] = $this->buildEntityFromFormData($entityData, $expectedType);
            } else {
                $callArgs[] = $this->resolveArgument($argConfig);
            }
        }

        $saveInstance->{$methodName}(...$callArgs);
    }

    // -------------------------------------------------------------------------
    // Entity hydration
    // -------------------------------------------------------------------------

    private function buildEntityFromFormData(array $entityData, string $entityType): object
    {
        $entity = $this->createOrLoadEntity($entityData, $entityType);

        // populateWithArray handles the setters declared in the interface
        $this->dataObjectHelper->populateWithArray($entity, $entityData, $entityType);

        // Applies magic setters (__call) for data outside the interface
		// e.g., setStores() on CMS Block that is not in BlockInterface
        $this->applyUnmappedSetters($entity, $entityData);

        return $entity;
    }

    /**
		* Calls setters via __call for keys that populateWithArray ignores
		* because they are not declared in the interface.
     */
    private function applyUnmappedSetters(object $entity, array $data): void
    {
        if (!method_exists($entity, '__call')) {
            return;
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($key, '_'));

            // Only calls setters that are not true methods
			// (those are already handled by populateWithArray)
            if (!method_exists($entity, $setter)) {
                try {
                    $entity->$setter($value);
                } catch (\Exception $e) {
                   
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Entity loading
    // -------------------------------------------------------------------------

    private function createOrLoadEntity(array $entityData, string $entityType): object
    {
        $id = $this->findIdInData($entityData);

        if ($id) {
            $repository = $this->resolveRepository($entityType);
            if ($repository && method_exists($repository, 'getById')) {
                try {
                    return $repository->getById((int) $id);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    
                }
            }
        }

        $factoryClass = $entityType . 'Factory';
        return $this->om->create($factoryClass)->create();
    }

    private function resolveRepository(string $entityType): ?object
    {
        $repositoryClass = str_replace('\\Data\\', '\\', $entityType);
        $repositoryClass = str_replace('Interface', 'RepositoryInterface', $repositoryClass);

        if (interface_exists($repositoryClass) || class_exists($repositoryClass)) {
            try {
                return $this->om->create($repositoryClass);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Only searches for explicit 'id' and 'entity_id' keys.
     * No regular expressions on _id to avoid false positives (store_id, website_id...).
     */
    private function findIdInData(array $data): ?string
    {
        if (!empty($data['id']))
            return (string) $data['id'];
        if (!empty($data['entity_id']))
            return (string) $data['entity_id'];

        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isAjaxRequest(): bool
    {
        $body = $this->getRequest()->getContent();
        if ($body) {
            $decoded = json_decode($body, true);
            if (isset($decoded['is_ajax']) && $decoded['is_ajax'] === true) {
                return true;
            }
        }

        return $this->getRequest()->isXmlHttpRequest()
            || $this->getRequest()->getParam('is_ajax') === 'true';
    }

    private function filterFormData(array $formData): array
    {
        $exclude = ['form_name', 'form_key', 'KEY', 'isAjax', 'is_ajax', 'uenc'];

        return array_filter(
            $formData,
            static fn($key) => !in_array($key, $exclude, true) && strpos($key, 'form_') !== 0,
            ARRAY_FILTER_USE_KEY
        );
    }

    private function getMethodParamTypes(string $className, string $methodName): array
    {
        try {
            $ref = new \ReflectionClass($className);
            $method = $ref->getMethod($methodName);
        } catch (\ReflectionException $e) {
            $instance = $this->om->create($className);
            $ref = new \ReflectionClass($instance);
            $method = $ref->getMethod($methodName);
        }

        $types = [];
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $types[$param->getName()] = $type->getName();
            }
        }

        return $types;
    }

    private function resolveArgument(array $argConfig): mixed
    {
        if (isset($argConfig['requestParam'])) {
            return $this->getRequest()->getParam($argConfig['requestParam']);
        }

        return $argConfig['value'] ?? null;
    }
}
