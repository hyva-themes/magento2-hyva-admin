<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\TypeReflection\CustomAttributesExtractor;
use Hyva\Admin\Model\TypeReflection\ExtensionAttributeTypeExtractor;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;

class FormLoadEntity
{
    private $value;

    private CustomAttributesExtractor $customAttributesExtractor;

    private ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor;

    private string $valueType;

    public function __construct(
        $value,
        string $valueType,
        CustomAttributesExtractor $customAttributesExtractor,
        ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor
    ) {
        $this->value                           = $value;
        $this->valueType = $valueType;
        $this->customAttributesExtractor       = $customAttributesExtractor;
        $this->extensionAttributeTypeExtractor = $extensionAttributeTypeExtractor;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return FormFieldDefinitionInterface[]
     */
    public function getFieldDefinitions(): array
    {
        $customAttributes = $this->customAttributesExtractor->isEavEntity($this->value)
            ? $this->customAttributesExtractor->attributesForInstanceAsFieldNames($this->value, $this->valueType)
            : [];
        $extensionAttributes = $this->extensionAttributeTypeExtractor->forType($this->valueType);
        // if object
        //   get getter fields
        // if array or plain data object
        //   return array keys(?)
    }
}
