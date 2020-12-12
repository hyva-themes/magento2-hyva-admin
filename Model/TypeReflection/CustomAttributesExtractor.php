<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\AbstractEntity as AbstractEavEntityResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
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

    private MagentoOrmReflection $magentoOrmReflection;

    public function __construct(
        DiConfigInterface $diConfig,
        ObjectManagerInterface $objectManager,
        ResourceConnection $dbResource,
        EavConfig $eavConfig,
        MagentoOrmReflection $magentoOrmReflection
    ) {
        $this->diConfig             = $diConfig;
        $this->dbResource           = $dbResource;
        $this->eavConfig            = $eavConfig;
        $this->objectManager        = $objectManager;
        $this->magentoOrmReflection = $magentoOrmReflection;
    }

    public function attributesForTypeAsFieldNames(string $type): array
    {
        return keys($this->attributesForType($type));
    }

    /**
     * @param string $type
     * @return AbstractAttribute[]
     */
    private function attributesForType(string $type): array
    {
        $entityTypeCode = $this->getEntityTypeCodeForType($type);
        return $entityTypeCode
            ? $this->eavConfig->getEntityAttributes($entityTypeCode)
            : [];
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

    private function getEntityTypeCodeForType(string $type): ?string
    {
        $resourceModelClass = $this->magentoOrmReflection->getResourceModelClassForType($type);

        if (is_subclass_of($resourceModelClass, AbstractEavEntityResource::class)) {
            /** @var AbstractEavEntityResource $resourceModel */
            $resourceModel = $this->objectManager->get($resourceModelClass);
            return $resourceModel->getEntityType()->getEntityTypeCode();
        }

        // fall back to eav entity type table for legacy eav types like order
        return $this->selectEntityTypeCode($resourceModelClass);
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
        return $this->isAttributeWithSourceModel($attribute) || $this->isAttributeWithOptionsInputRenderer($attribute);
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
