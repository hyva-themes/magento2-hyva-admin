<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class GridXmlToArrayConverter
{
    public function convert(\DOMDocument $dom): array
    {
        $root = $this->getRootElement($dom);

        return filter([
            'source'      => $this->convertSourceConfig($root),
            'columns'     => $this->convertColumnsConfig($root),
            'navigation'  => $this->convertNavigationConfig($root),
            'entity'      => $this->convertEntityConfig($root),
            'actions'     => $this->convertActionsConfig($root),
            'massActions' => $this->convertMassActionsConfig($root),
        ]);
    }

    private function convertSourceConfig(\DOMElement $root): array
    {
        $sourceElement = $this->getChildByName($root, 'source');
        return $sourceElement
            ? merge(
                $this->getTypeAttribute($sourceElement),
                $this->getArrayProviderSourceConfig($sourceElement),
                $this->getRepositoryProviderSourceConfig($sourceElement),
                $this->getCollectionSourceConfig($sourceElement),
                $this->getQuerySourceConfig($sourceElement),
                $this->getDefaultSourceCriteriaBindingsConfig($sourceElement)
            )
            : [];
    }

    private function getTypeAttribute(\DOMElement $source): array
    {
        $typeAttribute = $source->getAttribute('type');
        return $typeAttribute !== '' ? ['@type' => $typeAttribute] : [];
    }

    private function getSourceConfig(\DOMElement $source, string $name): array
    {
        $repositoryElement = $this->getChildByName($source, $name);
        return $repositoryElement ? [$name => $repositoryElement->nodeValue] : [];
    }

    private function getRootElement(\DOMDocument $document): \DOMElement
    {
        return $this->getAllChildElements($document)[0];
    }

    /**
     * @param \DOMNode $parent
     * @return \DOMElement[]
     */
    private function getAllChildElements(\DOMNode $parent): array
    {
        return values(filter(
            iterator_to_array($parent->childNodes),
            function (\DOMNode $childNode) {
                return $childNode->nodeType === \XML_ELEMENT_NODE;
            }
        ));
    }

    /**
     * @param \DOMElement $parent
     * @param string $name
     * @return \DOMElement[]
     */
    private function getChildrenByName(\DOMElement $parent, string $name): array
    {
        return values(filter($this->getAllChildElements($parent), function (\DOMElement $child) use ($name) {
            return $child->nodeName === $name;
        }));
    }

    /**
     * @param \DOMElement $element
     * @param string $name
     * @return string[]
     */
    private function getAttributeConfig(\DOMElement $element, string $name, string $withIndexKey = null): array
    {
        $idx = $withIndexKey ?? $name;
        $value = $element->getAttribute($name);
        return $value !== ''
            ? [$idx => $value]
            : [];
    }

    private function getElementConfig(\DOMElement $element, string $name): array
    {
        $childElement = $this->getChildByName($element, $name);
        return $childElement
            ? filter([$name => trim($childElement->nodeValue)])
            : [];
    }

    private function getChildByName(\DOMElement $parent, string $name): ?\DOMElement
    {
        return $this->getChildrenByName($parent, $name)[0] ?? null;
    }

    private function getArrayProviderSourceConfig(\DOMElement $source): array
    {
        /* <source type="array">
         *     <arrayProvider>ArrayProviderInterface</arrayProvider>
         * </source>
         */
        return $this->getSourceConfig($source, 'arrayProvider');
    }

    private function getRepositoryProviderSourceConfig(\DOMElement $source): array
    {
        /*
         * <source type="repository">
         *     <repositoryListMethod>Repository::getList</repositoryListMethod>
         * </source>
         */
        return $this->getSourceConfig($source, 'repositoryListMethod');
    }

    private function getCollectionSourceConfig(\DOMElement $source): array
    {
        /*
         * <source type="collection">
         *     <collection>SomeCollectionClass</collection>
         * </source>
         */
        return $this->getSourceConfig($source, 'collection');
    }

    /*
     * TODO: his is currently just an unfinished sketch of the method.
     */
    private function getQuerySourceConfig(?\DOMElement $source): array
    {
        /*
         * <source type="query">
         *     <query>
         *         <select>
         *             <column name="id"/>
         *             <column name="name"/>
         *             <column name="t.speed"/>
         *         </select>
         *         <from table="fossa_table" as="foo"/>
         *         <join type="left" table="other_table" alias="t">
         *             <condition>foo.id=t.id</condition>
         *         </join>
         *         <where>
         *             <and>fossa.product_id IN(:product_ids)</and>
         *         </where>
         *     </query>
         *     <bindParamenters>
         *         <product_ids>product_ids</product_ids>
         *     </bindParamenters>
         * </source>
         */
        $query = $this->getChildByName($source, 'query');
        if ($query) {
            $bindParameters = $this->getChildByName($source, 'bindParameters');
            // todo: unfinished business. Needs more thought and will be built out last.
            $queryConfig = ['query' => [], 'bindParams' => ($bindParameters ?? [])];
        }
        return $queryConfig ?? [];
    }

    private function getDefaultSourceCriteriaBindingsConfig(\DOMElement $sourceElement): array
    {
        $bindingsElement = $this->getChildByName($sourceElement, 'defaultSearchCriteriaBindings');
        return $bindingsElement
            ? ['defaultSearchCriteriaBindings' => $this->getDefaultSourceCriteriaBindingFieldsConfig($bindingsElement)]
            : [];
    }

    private function getDefaultSourceCriteriaBindingFieldsConfig(\DOMElement $bindingsElement): array
    {
        /*
         * <defaultSearchCriteriaBindings>
         *     <field name="my_id" requestParam="id"/>
         *     <field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
         *     <field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
         *     <field name="customer_ids" condition="finset" method="Magento\Customer\Model\Session::getCustomerId"/>
         * </defaultSearchCriteriaBindings>
         */
        return map(function (\DOMElement $fieldElement): array {
            return filter(merge(
                $this->getAttributeConfig($fieldElement, 'name', 'field'),
                $this->getAttributeConfig($fieldElement, 'requestParam'),
                $this->getAttributeConfig($fieldElement, 'method'),
                $this->getAttributeConfig($fieldElement, 'param'),
                $this->getAttributeConfig($fieldElement, 'property'),
                $this->getAttributeConfig($fieldElement, 'condition'),
            ));
        }, $this->getChildrenByName($bindingsElement, 'field'));
    }

    private function convertColumnsConfig(\DOMElement $root): array
    {
        $columnsElement = $this->getChildByName($root, 'columns');
        return $columnsElement
            ? $this->buildColumnsConfig($columnsElement)
            : [];
    }

    private function buildColumnsConfig(\DOMElement $columnsElement): array
    {
        /*
         * <columns rowAction="edit">
         *     <include keepColumnsFromSource="true">
         *         <column name="id" sortOrder="10"/>
         *         <column name="note" type="text"/>
         *         <column name="name" renderer="My\NameRendererBlock"/>
         *         <column name="speed" label="km/h" initiallyHidden="true"/>
         *         <column name="logo" renderAsUnsecureHtml="true" sortable="false"/>
         *         <column name="color" source="My\SourceModel"/>
         *         <column name="background_color">
         *             <option value="1" label="red"/>
         *             <option value="5" label="blue"/>
         *             <option value="3" label="black"/>
         *         </column>
         *     </include>
         *     <exclude>
         *         <column name="reference_id"/>
         *         <column name="internal_stuff"/>
         *     </exclude>
         * </columns>
         */
        return filter(merge(
            ['@rowAction' => $columnsElement->getAttribute('rowAction')],
            $this->getColumnsIncludeConfig($columnsElement),
            $this->getColumnsExcludeConfig($columnsElement),
        ));
    }

    private function getColumnsIncludeConfig(\DOMElement $columnsElement): array
    {
        $includesElement = $this->getChildByName($columnsElement, 'include');
        return $includesElement
            ? [
                '@keepAllSourceCols' => $includesElement->getAttribute('keepAllSourceColumns') ?? null,
                'include'            => map(
                    [$this, 'buildIncludeColumnConfig'],
                    $this->getChildrenByName($includesElement, 'column')
                ),
            ]
            : [];
    }

    private function buildIncludeColumnConfig(\DOMElement $columnElement): array
    {
        return filter(merge(
            $this->getAttributeConfig($columnElement, 'name', 'key'),
            $this->getAttributeConfig($columnElement, 'type'),
            $this->getAttributeConfig($columnElement, 'sortOrder'),
            $this->getAttributeConfig($columnElement, 'rendererBlockName'),
            $this->getAttributeConfig($columnElement, 'label'),
            $this->getAttributeConfig($columnElement, 'renderAsUnsecureHtml'),
            $this->getAttributeConfig($columnElement, 'sortable'),
            $this->getAttributeConfig($columnElement, 'source'),
            $this->getAttributeConfig($columnElement, 'template'),
            $this->getAttributeConfig($columnElement, 'initiallyHidden'),
            $this->getColumnOptionsConfig($columnElement),
        ));
    }

    private function getColumnOptionsConfig(\DOMElement $columnElement): array
    {
        /*
         * <column name="background_color">
         *     <option value="1" label="red"/>
         *     <option value="5" label="blue"/>
         *     <option value="3" label="black"/>
         * </column>
         */
        $options = map(function (\DOMElement $optionElement): array {
            return ['value' => $optionElement->getAttribute('value'), 'label' => $optionElement->getAttribute('label')];
        }, $this->getChildrenByName($columnElement, 'option'));
        return ['options' => $options];
    }

    private function getColumnsExcludeConfig(\DOMElement $columnsElement): array
    {
        /*
         * <exclude>
         *     <column name="reference_id"/>
         *     <column name="internal_stuff"/>
         * </exclude>
         */
        $excludeElement = $this->getChildByName($columnsElement, 'exclude');
        $getName        = function (\DOMElement $col): string {
            return $col->getAttribute('name');
        };
        return $excludeElement
            ? ['exclude' => values(filter(map($getName, $this->getChildrenByName($excludeElement, 'column'))))]
            : [];
    }

    private function convertNavigationConfig(\DOMElement $root): array
    {
        /*
         * <navigation useAjax="true">
         *     <pager>
         *         <defaultPageSize>10</defaultPageSize>
         *         <pageSizes>10,20,50,100</pageSizes>
         *     </pager>
         *     <sorting>
         *         <defaultSortByColumn>id</defaultSortByColumn>
         *         <defaultSortDirection>asc</defaultSortDirection>
         *     </sorting>
         *     <filters>
         *         <filter column="sku" enabled="true" template="Foo_Bar::filter.phtml"/>
         *         <filter column="color">
         *             <option label="reddish">
         *                 <value>16</value>
         *                 <value>17</value>
         *                 <value>18</value>
         *             </option>
         *             <option label="blueish">
         *                 <value>12</value>
         *             </option>
         *         </filter>
         *         <filter column="store_id" source="\Magento\Config\Model\Config\Source\Store"/>
         *     </filters>
         *     <buttons>
         *         <button id="add" label="Add" url="*\/*\/add"/>
         *         <button id="foo" label="Foo" onclick="doFoo"/>
         *     </buttons>
         *     <exports>
         *         <export id="csv" label="Export to CSV" class="Hyva\Admin\Model\Export\Csv" />
         *         <export id="xml" label="Export to CSV" class="Hyva\Admin\Model\Export\Xml" />
         *     </exports>
         * </navigation>
         */
        $navigationElement = $this->getChildByName($root, 'navigation');
        return $navigationElement
            ? filter([
                '@isAjaxEnabled' => $navigationElement->getAttribute('useAjax') ?? null,
                'pager'          => $this->getPagerConfig($navigationElement),
                'sorting'        => $this->getSortingConfig($navigationElement),
                'filters'        => $this->getFiltersConfig($navigationElement),
                'buttons'        => $this->getButtonsConfig($navigationElement),
                'exports'        => $this->getExportsConfig($navigationElement),
            ])
            : [];
    }

    private function getPagerConfig(\DOMElement $navigationElement): array
    {
        $pagerElement = $this->getChildByName($navigationElement, 'pager');
        return $pagerElement
            ? filter(merge(
                ['@enabled' => $pagerElement->getAttribute('enabled')],
                $this->getElementConfig($pagerElement, 'defaultPageSize'),
                $this->getElementConfig($pagerElement, 'pageSizes')
            ))
            : [];
    }

    private function getSortingConfig(\DOMElement $navigationElement): array
    {
        $sortingElement = $this->getChildByName($navigationElement, 'sorting');
        return $sortingElement
            ? filter(merge(
                $this->getElementConfig($sortingElement, 'defaultSortByColumn'),
                $this->getElementConfig($sortingElement, 'defaultSortDirection')
            ))
            : [];
    }

    /**
     * todo: build this when the entity config is used
     */
    private function convertEntityConfig(\DOMElement $root): array
    {
        /*
         * <entityConfig>
         *     <label>
         *         <singular>Fossa</singular>
         *         <plural>Fossas</plural>
         *     </label>
         * </entityConfig>
         */
        $entityConfigElement = $this->getChildByName($root, 'entityConfig');
        return $entityConfigElement
            ? filter(['label' => $this->convertEntityLabelConfig($entityConfigElement)])
            : [];

    }

    private function convertEntityLabelConfig(\DOMElement $entityConfigElement): array
    {
        $labelElement = $this->getChildByName($entityConfigElement, 'label');
        return $labelElement
            ? filter(merge(
                $this->getElementConfig($labelElement, 'singular'),
                $this->getElementConfig($labelElement, 'plural'),
            ))
            : [];
    }

    private function convertActionsConfig(\DOMElement $root): array
    {
        /*
         * <actions idColumn="name">
         *     <action id="edit" label="Edit" url="*\/*\/edit" idParam="id"/>
         *     <action id="delete" label="Delete" url="*\/*\/delete"/>
         *     <action label="Validate" url="admin/dashboard"/>
         * </actions>
         */
        if ($actionsElement = $this->getChildByName($root, 'actions')) {
            $actions = filter(map([$this, 'convertActionConfig'], $this->getChildrenByName($actionsElement, 'action')));
            return merge(filter(['@idColumn' => $actionsElement->getAttribute('idColumn')]), ['actions' => $actions]);
        } else {
            return [];
        }
    }

    private function convertActionConfig(\DOMElement $actionElement): array
    {
        return filter(merge(
            $this->getAttributeConfig($actionElement, 'id'),
            $this->getAttributeConfig($actionElement, 'label'),
            $this->getAttributeConfig($actionElement, 'url'),
            $this->getAttributeConfig($actionElement, 'idParam'),
        ));
    }

    private function convertMassActionsConfig(\DOMElement $root)
    {
        /*
         * <massActions idColumn="name" idsParam="ids">
         *     <action label="Update" url="*\/massActions/update"/>
         *     <action label="Delete All" url="*\/massActions/delete" requireConfirmation="true"/>
         * </massActions>
         */
        if ($massActionsElement = $this->getChildByName($root, 'massActions')) {
            $massActionElements = $this->getChildrenByName($massActionsElement, 'action');
            $actions            = filter(map([$this, 'convertMassActionConfig'], $massActionElements));
            return filter([
                '@idColumn' => $massActionsElement->getAttribute('idColumn'),
                '@idsParam' => $massActionsElement->getAttribute('idsParam'),
                'actions'   => $actions,
            ]);
        } else {
            return [];
        }
    }

    private function convertMassActionConfig(\DOMElement $actionElement): array
    {
        return filter(merge(
            $this->getAttributeConfig($actionElement, 'id'),
            $this->getAttributeConfig($actionElement, 'label'),
            $this->getAttributeConfig($actionElement, 'url'),
            map(function (string $v): bool {
                return $v === 'true';
            }, $this->getAttributeConfig($actionElement, 'requireConfirmation')),
        ));
    }

    private function getButtonsConfig(\DOMElement $navigationElement): ?array
    {
        /*
         * <buttons>
         *     <button id="add" label="Add" url="*\/*\/add" enabled="true"/>
         *     <button id="foo" label="Foo" onclick="doFoo"/>
         *     <button id="bar" template="Module_Name::button.phtml"/>
         * </buttons>
         */
        $buttonsElement = $this->getChildByName($navigationElement, 'buttons');
        return $buttonsElement
            ? map([$this, 'getButtonConfig'], $this->getChildrenByName($buttonsElement, 'button'))
            : null;
    }

    private function getExportsConfig(\DOMElement $exportsElement): ?array
    {
        /*
         *     <exports>
         *         <export id="csv" label="Export to CSV" class="Hyva\Admin\Model\Export\Csv" />
         *         <export id="xml" label="Export to CSV" class="Hyva\Admin\Model\Export\Xml" />
         *     </exports>
         */
        $buttonsElement = $this->getChildByName($exportsElement, 'exports');
        return $buttonsElement
            ? map([$this, 'getExportConfig'], $this->getChildrenByName($buttonsElement, 'export'))
            : null;
    }

    private function getButtonConfig(\DOMElement $buttonsElement): ?array
    {
        return filter(merge(
            $this->getAttributeConfig($buttonsElement, 'id'),
            $this->getAttributeConfig($buttonsElement, 'label'),
            $this->getAttributeConfig($buttonsElement, 'template'),
            $this->getAttributeConfig($buttonsElement, 'url'),
            $this->getAttributeConfig($buttonsElement, 'onclick'),
            $this->getAttributeConfig($buttonsElement, 'sortOrder'),
            $this->getAttributeConfig($buttonsElement, 'enabled')
        ));
    }

    private function getExportConfig(\DOMElement $exportsElement): ?array
    {
        return filter(merge(
            $this->getAttributeConfig($exportsElement, 'id'),
            $this->getAttributeConfig($exportsElement, 'label'),
            $this->getAttributeConfig($exportsElement, 'fileName'),
            $this->getAttributeConfig($exportsElement, 'enabled'),
            $this->getAttributeConfig($exportsElement, 'class'),
            $this->getAttributeConfig($exportsElement, 'sortOrder')
        ));
    }

    private function getFiltersConfig(\DOMElement $navigationElement): ?array
    {
        /*
         * <filters>
         *     <filter column="sku" enabled="true" template="Foo_Bar::filter.phtml"/>
         *     <filter column="color">
         *         <option label="reddish">
         *             <value>16</value>
         *             <value>17</value>
         *             <value>18</value>
         *         </option>
         *         <option label="blueish">
         *             <value>12</value>
         *         </option>
         *     </filter>
         *     <filter column="store_id" source="\Magento\Config\Model\Config\Source\Store"/>
         * </filters>
         */
        $filtersElement = $this->getChildByName($navigationElement, 'filters');
        return $filtersElement
            ? map([$this, 'getFilterConfig'], $this->getChildrenByName($filtersElement, 'filter'))
            : null;
    }

    private function getFilterConfig(\DOMElement $filterElement): ?array
    {
        return merge(
            ['key' => $this->getAttributeConfig($filterElement, 'column')['column'] ?? null],
            $this->getAttributeConfig($filterElement, 'enabled'),
            $this->getAttributeConfig($filterElement, 'template'),
            $this->getAttributeConfig($filterElement, 'filterType'),
            $this->getAttributeConfig($filterElement, 'source'),
            $this->getFilterOptionsConfig($filterElement)
        );
    }

    private function getFilterOptionsConfig(\DOMElement $filterElement): array
    {
        /*
         * <filter column="color">
         *     <option label="reddish">
         *         <value>16</value>
         *         <value>17</value>
         *         <value>18</value>
         *     </option>
         * </filter>
         */
        $options = filter(map(function (\DOMElement $optionElement): ?array {
            $values = $this->getFilterOptionValuesConfig($optionElement);
            return $values
                ? ['label' => $optionElement->getAttribute('label'), 'values' => $values]
                : null;
        }, $this->getChildrenByName($filterElement, 'option')));
        return $options
            ? ['options' => $options]
            : [];
    }

    private function getFilterOptionValuesConfig(\DOMElement $optionElement): array
    {
        return map(function (\DOMElement $valueElement): string {
            return trim($valueElement->nodeValue);
        }, $this->getChildrenByName($optionElement, 'value'));
    }
}
