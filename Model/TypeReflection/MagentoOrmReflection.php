<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Eav\Model\Entity\AbstractEntity as AbstractEavResourceModel;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as AbstractFlatTableResourceModel;
use Magento\Framework\ObjectManager\ConfigInterface as DiConfigInterface;
use Magento\Framework\ObjectManagerInterface;

class MagentoOrmReflection
{
    /**
     * @var DiConfigInterface
     */
    private $diConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(DiConfigInterface $diConfig, ObjectManagerInterface $objectManager)
    {
        $this->diConfig      = $diConfig;
        $this->objectManager = $objectManager;
    }

    public function getEntityModelClassForType(string $type): string
    {
        $class = $this->diConfig->getPreference($type);

        return $this->removeProxySuffix($this->removeInterceptorSuffix($class));
    }

    public function getResourceModelClassForType(string $type): string
    {
        $class = $this->getEntityModelClassForType($type);
        return $this->isResourceModelClass($class)
            ? $class
            : $this->modelClassToResourceModel($class);
    }

    private function isResourceModelClass(string $type): bool
    {
        return strpos($type, 'ResourceModel') !== false;
    }

    private function modelClassToResourceModel(string $type): string
    {
        if (is_subclass_of($type, \Magento\Framework\Model\AbstractModel::class)) {
            /** @var \Magento\Framework\Model\AbstractModel $model */
            $model = $this->objectManager->create($type);
            return $model->getResourceName();
        }
        // The customer interface is not implemented by the customer ORM model, we handle that special case here.
        return is_subclass_of($type, \Magento\Customer\Api\Data\CustomerInterface::class)
            ? \Magento\Customer\Model\ResourceModel\Customer::class
            : preg_replace('#\\\Model\\\\#', '\Model\ResourceModel\\', $type);
    }

    private function removeProxySuffix(string $type): string
    {
        return $this->removeGeneratedClassSuffix($type, '\Proxy');
    }

    private function removeInterceptorSuffix(string $type): string
    {
        return $this->removeGeneratedClassSuffix($type, '\Interceptor');
    }

    private function removeGeneratedClassSuffix(string $class, string $suffix): string
    {
        $suffixLength = strlen($suffix);
        return substr($class, $suffixLength * -1) === $suffix
            ? substr($class, 0, $suffixLength * -1)
            : $class;
    }

    public function getIdFieldNameForType(string $type): ?string
    {
        $class              = $this->diConfig->getPreference($type);
        $entityModelClass   = $this->removeProxySuffix($this->removeInterceptorSuffix($class));
        $resourceModelClass = $this->getResourceModelClassForType($entityModelClass);

        return $this->extendsAbstractResourceModel($resourceModelClass)
            ? $this->getIdFieldFromResourceModel($resourceModelClass)
            : null;
    }

    private function getIdFieldFromResourceModel(string $resourceModelClass): string
    {
        /** @var AbstractFlatTableResourceModel|AbstractEavResourceModel $resourceModel */
        $resourceModel = $this->objectManager->get($resourceModelClass);

        return $resourceModel->getIdFieldName();
    }

    public function extendsAbstractResourceModel(string $class): bool
    {
        return is_subclass_of($class, AbstractResource::class);
    }
}
