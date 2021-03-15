<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class FormXmlToArrayConverter
{
    public function convert(\DOMDocument $dom): array
    {
        $root = XmlToArray::getRootElement($dom);

        return filter([
            'load'       => $this->convertMethod(XmlToArray::getChildByName($root, 'load')),
            'save'       => $this->convertMethod(XmlToArray::getChildByName($root, 'save')),
            'fields'     => $this->convertFields(XmlToArray::getChildByName($root, 'fields')),
            'sections'   => $this->convertSections(XmlToArray::getChildByName($root, 'sections')),
            'navigation' => $this->convertNavigation(XmlToArray::getChildByName($root, 'navigation')),
        ]);
    }

    private function convertMethod(?\DOMElement $methodElement): array
    {
        /*
         * <load method="\Magento\Customer\Api\CustomerRepositoryInterface::getById"
         *       type="\Magento\Customer\Api\Data\CustomerInterface">
         *     <bindArguments>
         *         <argument name="customer_id" requestParam="id"/>
         *         <argument name="foo" method="\My\Module\Model\Bar::getBaz" param="qux" property="quux"/>
         *         <argument name="bar" value="BAR"/>
         *     </bindArguments>
         * </load>
         * <save method="\Magento\Customer\Api\CustomerRepositoryInterface::save">
         *     <bindArguments>
         *         <argument name="customer" formData="true"/>
         *         <argument name="passwordHash" method="\My\Module\Model\CustomerPassword::hash"/>
         *     </bindArguments>
         * </save>
         * or also
         * <save method="\Magento\Cms\Model\ResourceModel\Block::save"/>
         */
        return $methodElement
            ? filter(merge(
                XmlToArray::getAttributeConfig($methodElement, 'method'),
                XmlToArray::getAttributeConfig($methodElement, 'type'),
                ['bindArguments' => $this->convertBindArguments($methodElement)]
            ))
            : [];
    }

    private function convertBindArguments(\DOMElement $parent): ?array
    {
        $callback = function (\DOMElement $argumentElement): array {
            return [
                $argumentElement->getAttribute('name') => filter(merge(
                    XmlToArray::getAttributeConfig($argumentElement, 'requestParam'),
                    XmlToArray::getAttributeConfig($argumentElement, 'formData'),
                    XmlToArray::getAttributeConfig($argumentElement, 'method'),
                    XmlToArray::getAttributeConfig($argumentElement, 'param'),
                    XmlToArray::getAttributeConfig($argumentElement, 'property'),
                    XmlToArray::getAttributeConfig($argumentElement, 'value'),
                )),
            ];
        };

        $bindArgumentsElement = XmlToArray::getChildByName($parent, 'bindArguments');
        return $bindArgumentsElement
            ? merge([], ...map($callback, XmlToArray::getChildrenByName($bindArgumentsElement, 'argument')))
            : null;
    }

    private function convertFields(?\DOMElement $fieldsElement): array
    {
        $includeElement      = $fieldsElement ? XmlToArray::getChildByName($fieldsElement, 'include') : null;
        $keepAllSourceFields = $includeElement ?
            XmlToArray::getAttributeConfig($includeElement, 'keepAllSourceFields', '@keepAllSourceFields') :
            [];
        return $fieldsElement
            ? filter(merge(
                $keepAllSourceFields,
                ['include' => $this->convertIncludeFields(XmlToArray::getChildByName($fieldsElement, 'include'))],
                ['exclude' => $this->convertExcludeFields(XmlToArray::getChildByName($fieldsElement, 'exclude'))]
            ))
            : [];
    }

    private function convertIncludeFields(?\DOMElement $includeElement): array
    {
        /*
         * <fields>
         *     <include keepAllSourceFields="true">
         *         <field name="identifier" group="important-things"/>
         *         <field name="title" template="My_Module::form/title-field.phtml"/>
         *         <field name="content" type="wysiwyg"/>
         *         <field name="creation_time" type="datetime"/>
         *         <field name="is_active" type="boolean"/>
         *         <field name="comment" enabled="false"/>
         *         <field name="store_ids" type="select" source="\Magento\Eav\Model\Entity\Attribute\Source\Store"/>
         *         <field name="admin" valueProcessor="\My\Module\Form\AdminLinkProcessor"/>
         *     </include>
         * </fields>
         */
        return $includeElement
            ? filter(map([$this, 'convertIncludeField'], XmlToArray::getChildrenByName($includeElement, 'field')))
            : [];
    }

    private function convertIncludeField(\DOMElement $fieldElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($fieldElement, 'name'),
            XmlToArray::getAttributeConfig($fieldElement, 'group'),
            XmlToArray::getAttributeConfig($fieldElement, 'template'),
            XmlToArray::getAttributeConfig($fieldElement, 'type'),
            XmlToArray::getAttributeConfig($fieldElement, 'enabled'),
            XmlToArray::getAttributeConfig($fieldElement, 'source'),
            XmlToArray::getAttributeConfig($fieldElement, 'valueProcessor'),
        ));
    }

    private function convertExcludeFields(?\DOMElement $excludeElement): array
    {
        /*
         * <fields>
         *     <exclude>
         *         <field name="updated_at"/>
         *     </exclude>
         * </fields>
         */
        $getFieldName = function (\DOMElement $field): string {
            return $field->getAttribute('name');
        };
        return $excludeElement
            ? values(filter(map($getFieldName, XmlToArray::getChildrenByName($excludeElement, 'field'))))
            : [];
    }

    private function convertSections(?\DOMElement $sectionsElement): array
    {
        /*
         * <sections>
         *     <section id="foo" label="Foos" sortOrder="10">
         *         <group id="important-things" sortOrder="10"/>
         *         <group id="details" sortOrder="20" label="Details"/>
         *     </section>
         *     <section id="bar" label="Bars" sortOrder="20">
         *         <group id="whatever" sortOrder="10"/>
         *     </section>
         * </sections>
         */
        return $sectionsElement
            ? map([$this, 'convertSection'], XmlToArray::getChildrenByName($sectionsElement, 'section'))
            : [];
    }

    private function convertSection(\DOMElement $sectionElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($sectionElement, 'id'),
            XmlToArray::getAttributeConfig($sectionElement, 'label'),
            XmlToArray::getAttributeConfig($sectionElement, 'sortOrder'),
            ['groups' => map([$this, 'convertSectionGroup'], XmlToArray::getChildrenByName($sectionElement, 'group'))]
        ));
    }

    private function convertSectionGroup(\DOMElement $groupElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($groupElement, 'id'),
            XmlToArray::getAttributeConfig($groupElement, 'label'),
            XmlToArray::getAttributeConfig($groupElement, 'sortOrder')
        ));
    }

    private function convertNavigation(?\DOMElement $navigationElement): array
    {
        /*
         * <navigation>
         *     <buttons>
         *         <button id="save" label="Save" url="hyva_admin/form/save" enabled="false" />
         *         <button id="only-visible-when-entity-was-loaded" label="Example" hiddenForNewEntity="true"/>
         *         <button id="reset" label="Reset" url="* /* /*"/>
         *     </buttons>
         * </navigation>
         */
        return $navigationElement
            ? filter([
                'buttons' => $this->convertButtons(XmlToArray::getChildByName($navigationElement, 'buttons')),
            ])
            : [];
    }

    private function convertButtons(?\DOMElement $buttonsElement): array
    {
        return $buttonsElement
            ? values(filter(map([$this, 'convertButton'], XmlToArray::getChildrenByName($buttonsElement, 'button'))))
            : [];
    }

    private function convertButton(\DOMElement $buttonElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($buttonElement, 'id'),
            XmlToArray::getAttributeConfig($buttonElement, 'label'),
            XmlToArray::getAttributeConfig($buttonElement, 'url'),
            XmlToArray::getAttributeConfig($buttonElement, 'enabled'),
            XmlToArray::getAttributeConfig($buttonElement, 'hiddenForNewEntity'),
        ));
    }
}
