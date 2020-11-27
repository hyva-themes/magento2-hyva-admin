<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\AbstractEntity as AbstractEavEntityResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\Element\Multiselect;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\ObjectManager\ConfigInterface as DiConfigInterface;
use Magento\Framework\ObjectManagerInterface;

use function array_keys as keys;

class CustomAttributesExtractor
{
    private ResourceConnection $dbResource;

    private EavConfig $eavConfig;

    private DiConfigInterface $diConfig;

    private ObjectManagerInterface $objectManager;

    public function __construct(
        DiConfigInterface $diConfig,
        ObjectManagerInterface $objectManager,
        ResourceConnection $dbResource,
        EavConfig $eavConfig
    ) {
        $this->diConfig      = $diConfig;
        $this->dbResource    = $dbResource;
        $this->eavConfig     = $eavConfig;
        $this->objectManager = $objectManager;
    }

    public function attributesForTypeAsFieldNames(string $type): array
    {
        return keys($this->attributesForType($type));
    }

    /**
     * @param string $type
     * @return AbstractAttribute[]
     */
    public function attributesForType(string $type): array
    {
        $entityTypeCode = $this->getEntityTypeCodeForType($type);
        return $entityTypeCode
            ? $this->eavConfig->getEntityAttributes($entityTypeCode)
            : [];
    }

    private function getEntityTypeCodeByEntityModelClass(string $entityModelClass): ?string
    {
        $resourceModelClass = $this->isResourceModelClass($entityModelClass)
            ? $entityModelClass
            : $this->modelClassToResourceModel($entityModelClass);

        if (is_subclass_of($resourceModelClass, AbstractEavEntityResource::class)) {
            /** @var AbstractEavEntityResource $resourceModel */
            $resourceModel = $this->objectManager->get($resourceModelClass);
            return $resourceModel->getEntityType()->getEntityTypeCode();
        }

        // fall back
        return $this->selectEntityTypeCode($resourceModelClass);
    }

    private function selectEntityTypeCode(string $resourceModelClass): ?string
    {
        $db             = $this->dbResource->getConnection();
        $tableName      = $this->dbResource->getTableName('eav_entity_type');
        $select         = $db->select()->from($tableName, 'entity_type_code')
                             ->where('entity_model=?', ltrim($resourceModelClass, '\\'));
        $entityTypeCode = $db->fetchOne($select);

        return $entityTypeCode ? $entityTypeCode : null;
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

    private function isResourceModelClass(string $type): bool
    {
        return strpos($type, 'ResourceModel') !== false;
    }

    private function getEntityTypeCodeForType(string $type): ?string
    {
        $class            = $this->diConfig->getPreference($type);
        $entityModelClass = $this->removeProxySuffix($this->removeInterceptorSuffix($class));

        return $this->getEntityTypeCodeByEntityModelClass($entityModelClass);
    }

    public function getAttributeBackendType($type, $code): ?string
    {
        $entityTypeCode = $this->getEntityTypeCodeForType($type);
        $attribute      = $this->eavConfig->getAttribute($entityTypeCode, $code);
        return $this->isArrayAttribute($attribute) || !$attribute->getBackend()->isScalar()
            ? 'array'
            : ($attribute->getFrontendInput() === 'gallery' ? 'gallery' : $attribute->getBackendType());
    }

    private function isArrayAttribute(AbstractAttribute $attribute): bool
    {
        return $this->isAttributeWithSourceModel($attribute) ||  $this->isAttributeWithOptionsInputRenderer($attribute);
    }

    private function isAttributeWithOptionsInputRenderer(AbstractAttribute $attribute): bool
    {
        if (!($frontendInputRenderer = $attribute->getFrontendInputRenderer())) {
            return false;
        }
        return
            is_subclass_of($frontendInputRenderer, Multiselect::class) ||
            is_subclass_of($frontendInputRenderer, Select::class);
    }

    private function isAttributeWithSourceModel(AbstractAttribute $attribute): bool
    {
        return
            $attribute->getSourceModel() ||
            in_array($attribute->getFrontendInput(), ['multiselect', 'select'], true);
    }

    public function getAttributeLabel(string $type, string $code): ?string
    {
        $entityTypeCode = $this->getEntityTypeCodeForType($type);
        $attribute      = $this->eavConfig->getAttribute($entityTypeCode, $code);

        return $attribute->getDefaultFrontendLabel();
    }

    public function getValue($record, string $key)
    {
        if ($record instanceof CustomAttributesDataInterface && $record->getCustomAttribute($key)) {
            return $record->getCustomAttribute($key)->getValue();
        } elseif (method_exists($record, 'getData')) {
            return $record->getData($key);
        }
        // The attribute may not be set on the given entity instance
        return null;
    }

    public function getAttributeOptions(string $type, string $code): array
    {
        $entityTypeCode = $this->getEntityTypeCodeForType($type);
        $attribute      = $this->eavConfig->getAttribute($entityTypeCode, $code);

        $sourceModel = $this->isArrayAttribute($attribute) ? $attribute->getSource() : null;

        return $sourceModel
            ? $sourceModel->getAllOptions()
            : [];
    }
}
