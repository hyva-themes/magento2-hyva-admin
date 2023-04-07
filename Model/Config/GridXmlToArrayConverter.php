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
        $root = XmlToArray::getRootElement($dom);

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
        $sourceElement = XmlToArray::getChildByName($root, 'source');
        return $sourceElement
            ? merge(
                $this->getTypeAttribute($sourceElement),
                $this->getArrayProviderSourceConfig($sourceElement),
                $this->getRepositoryProviderSourceConfig($sourceElement),
                $this->getCollectionSourceConfig($sourceElement),
                $this->getQuerySourceConfig($sourceElement),
                $this->getDefaultSourceCriteriaBindingsConfig($sourceElement),
                $this->getSourceProcessorsConfig($sourceElement)
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
        $repositoryElement = XmlToArray::getChildByName($source, $name);
        return $repositoryElement ? [$name => $repositoryElement->nodeValue] : [];
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

    private function getQuerySourceConfig(?\DOMElement $source): array
    {
        /*
         * <source>
         *     <query unionSelectType="distinct">
         *         <select>
         *             <from table="catalog_product" as="main_table"/>
         *             <columns>
         *                 <column name="entity_id" as="id"/>
         *                 <column name="sku"/>
         *             </columns>
         *             <join type="left" table="catalog_product_entity_varchar" as="t_name">
         *                 <on>t_name.entity_id=main_table.entity_id AND attribute_id=47</on>
         *                 <columns>
         *                     <column name="value" as="name"/>
         *                 </columns>
         *             </join>
         *             <groupBy>
         *                 <column name="main_table.attribute_set_id"/>
         *                 <column name="t_name.name"/>
         *             <groupBy>
         *         </select>
         *         <unionSelect>
         *             <from table="catalog_product_other"/>
         *          </unionSelect>
         *     </query>
         * </source>
         */
        $query = XmlToArray::getChildByName($source, 'query');
        return $query
            ? [
                'query' => filter(merge(
                    XmlToArray::getAttributeConfig($query, 'unionSelectType', '@unionSelectType'),
                    ['select' => $this->getQuerySelectConfig(XmlToArray::getChildByName($query, 'select'))],
                    [
                        'unions' => values(filter(map(
                            [$this, 'getQueryUnionSelectConfig'],
                            XmlToArray::getChildrenByName($query, 'unionSelect')
                        ))),
                    ]
                )),
            ]
            : [];
    }

    private function getQuerySelectConfig(?\DOMElement $selectElement): array
    {
        return $selectElement
            ? filter([
                'from'    => $this->getQueryFromConfig(XmlToArray::getChildByName($selectElement, 'from')),
                'columns' => $this->getQueryColumnsConfig(XmlToArray::getChildByName($selectElement, 'columns')),
                'joins'   => map([$this, 'getQueryJoinConfig'], XmlToArray::getChildrenByName($selectElement, 'join')),
                'groupBy' => $this->getQueryGroupByConfig(XmlToArray::getChildByName($selectElement, 'groupBy')),
            ])
            : [];
    }

    private function getQueryFromConfig(?\DOMElement $fromElement): array
    {
        /* <from table="catalog_product" as="main_table"/> */
        return $fromElement
            ? merge(
                XmlToArray::getAttributeConfig($fromElement, 'table'),
                XmlToArray::getAttributeConfig($fromElement, 'as', '@as')
            )
            : [];
    }

    private function getQueryColumnsConfig(?\DOMElement $columnsElement): array
    {
        /*
         * <columns>
         *     <column name="entity_id" as="id"/>
         *     <column name="sku"/>
         *     <expression as="count">COUNT(*)</expression>
         * </columns>
         */
        return $columnsElement
            ? filter(merge(
                map([$this, 'getQuerySelectColumn'], XmlToArray::getChildrenByName($columnsElement, 'column')),
                map([$this, 'getQuerySelectExpression'], XmlToArray::getChildrenByName($columnsElement, 'expression'))
            ))
            : [];
    }

    private function getQuerySelectColumn(\DOMElement $columnElement): array
    {
        return merge(
            XmlToArray::getAttributeConfig($columnElement, 'name', 'column'),
            XmlToArray::getAttributeConfig($columnElement, 'as', '@as')
        );
    }

    private function getQuerySelectExpression(\DOMElement $expressionElement): array
    {
        return filter(merge(
            ['expression' => trim($expressionElement->textContent)],
            XmlToArray::getAttributeConfig($expressionElement, 'as', '@as')
        ));
    }

    private function getQueryJoinConfig(\DOMElement $joinElement): array
    {
        /*
         * <join type="left" table="catalog_product_entity_varchar" as="t_name">
         *     <on>t_name.entity_id=main_table.entity_id AND attribute_id=47</on>
         *     <columns>
         *         <column name="value" as="name"/>
         *     </columns>
         * </join>
         */
        return filter(merge(
            XmlToArray::getAttributeConfig($joinElement, 'type'),
            [
                'join' => filter(merge(
                    XmlToArray::getAttributeConfig($joinElement, 'table'),
                    XmlToArray::getAttributeConfig($joinElement, 'as', '@as')
                )),
            ],
            XmlToArray::getElementConfig($joinElement, 'on'),
            ['columns' => $this->getQueryColumnsConfig(XmlToArray::getChildByName($joinElement, 'columns'))]
        ));
    }

    private function getQueryGroupByConfig(?\DOMElement $groupByElement): array
    {
        /*
         * <groupBy>
         *     <column name="main_table.attribute_set_id"/>
         *     <column name="t_name.name"/>
         * <groupBy>
         */
        return $groupByElement
            ? values(filter(map(function (\DOMElement $groupByColumnElement): array {
                return filter(merge(
                    XmlToArray::getAttributeConfig($groupByColumnElement, 'name', 'column')
                ));
            }, XmlToArray::getChildrenByName($groupByElement, 'column'))))
            : [];
    }

    private function getQueryUnionSelectConfig(\DOMElement $unionSelectConfig): array
    {
        /*
         * <unionSelect>
         *    <from table="catalog_product_other"/>
         * </unionSelect>
         */
        return $this->getQuerySelectConfig($unionSelectConfig);
    }

    private function getDefaultSourceCriteriaBindingsConfig(\DOMElement $sourceElement): array
    {
        $bindingsElement = XmlToArray::getChildByName($sourceElement, 'defaultSearchCriteriaBindings');
        return $bindingsElement
            ? ['defaultSearchCriteriaBindings' => $this->getDefaultSourceCriteriaBindingFieldsConfig($bindingsElement)]
            : [];
    }

    private function getDefaultSourceCriteriaBindingFieldsConfig(\DOMElement $bindingsElement): array
    {
        /*
         * <defaultSearchCriteriaBindings combineConditionsWith="or">
         *     <field name="my_id" requestParam="id"/>
         *     <field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
         *     <field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
         *     <field name="customer_ids" condition="finset" method="Magento\Customer\Model\Session::getCustomerId"/>
         *     <field name="foo" value="FOO" condition="neq"/>
         * </defaultSearchCriteriaBindings>
         */
        $combineConditionsWith = XmlToArray::getAttributeConfig($bindingsElement, 'combineConditionsWith');
        $fields = map(function (\DOMElement $fieldElement): array {
            return filter(merge(
                XmlToArray::getAttributeConfig($fieldElement, 'name', 'field'),
                XmlToArray::getAttributeConfig($fieldElement, 'requestParam'),
                XmlToArray::getAttributeConfig($fieldElement, 'method'),
                XmlToArray::getAttributeConfig($fieldElement, 'param'),
                XmlToArray::getAttributeConfig($fieldElement, 'property'),
                XmlToArray::getAttributeConfig($fieldElement, 'condition'),
                XmlToArray::getAttributeConfig($fieldElement, 'value'),
            ));
        }, XmlToArray::getChildrenByName($bindingsElement, 'field'));
        return filter(merge($combineConditionsWith, ['fields' => $fields]));
    }

    private function getSourceProcessorsConfig(\DOMElement $sourceElement): array
    {
        /*
         * <source>
         *     <processors>
         *         <processor class="\Hyva\Aaaa\Model\GridProcessorA" enabled="true"/>
         *         <processor class="\Hyva\Bbbb\Model\GridProcessorB" enabled="false"/>
         *         <processor class="\Hyva\Cccc\Model\GridProcessorC"/>
         *     </processors>
         * </source>
         */
        $processorsElement = XmlToArray::getChildByName($sourceElement, 'processors');
        return $processorsElement
            ? filter([
                'processors' => map(
                    [$this, 'getSourceProcessorConfig'],
                    XmlToArray::getChildrenByName($processorsElement, 'processor')
                ),
            ])
            : [];
    }

    private function getSourceProcessorConfig(\DOMElement $processorElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($processorElement, 'class'),
            XmlToArray::getAttributeConfig($processorElement, 'enabled')
        ));
    }

    private function convertColumnsConfig(\DOMElement $root): array
    {
        $columnsElement = XmlToArray::getChildByName($root, 'columns');
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
        $includesElement = XmlToArray::getChildByName($columnsElement, 'include');
        return $includesElement
            ? [
                '@keepAllSourceCols' => $includesElement->getAttribute('keepAllSourceColumns') ?? null,
                'include'            => map(
                    [$this, 'buildIncludeColumnConfig'],
                    XmlToArray::getChildrenByName($includesElement, 'column')
                ),
            ]
            : [];
    }

    private function buildIncludeColumnConfig(\DOMElement $columnElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($columnElement, 'name', 'key'),
            XmlToArray::getAttributeConfig($columnElement, 'type'),
            XmlToArray::getAttributeConfig($columnElement, 'sortOrder'),
            XmlToArray::getAttributeConfig($columnElement, 'rendererBlockName'),
            XmlToArray::getAttributeConfig($columnElement, 'label'),
            XmlToArray::getAttributeConfig($columnElement, 'renderAsUnsecureHtml'),
            XmlToArray::getAttributeConfig($columnElement, 'sortable'),
            XmlToArray::getAttributeConfig($columnElement, 'source'),
            XmlToArray::getAttributeConfig($columnElement, 'template'),
            XmlToArray::getAttributeConfig($columnElement, 'initiallyHidden'),
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
        }, XmlToArray::getChildrenByName($columnElement, 'option'));
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
        $excludeElement = XmlToArray::getChildByName($columnsElement, 'exclude');
        $getName        = function (\DOMElement $col): string {
            return $col->getAttribute('name');
        };
        return $excludeElement
            ? ['exclude' => values(filter(map($getName, XmlToArray::getChildrenByName($excludeElement, 'column'))))]
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
         *         <export type="csv" label="Export to CSV" class="Hyva\Admin\Model\Export\Csv" />
         *         <export type="xml" label="Export to XML" class="Hyva\Admin\Model\Export\Xml" />
         *     </exports>
         * </navigation>
         */
        $navigationElement = XmlToArray::getChildByName($root, 'navigation');
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
        $pagerElement = XmlToArray::getChildByName($navigationElement, 'pager');
        return $pagerElement
            ? filter(merge(
                ['@enabled' => $pagerElement->getAttribute('enabled')],
                XmlToArray::getElementConfig($pagerElement, 'defaultPageSize'),
                XmlToArray::getElementConfig($pagerElement, 'pageSizes')
            ))
            : [];
    }

    private function getSortingConfig(\DOMElement $navigationElement): array
    {
        $sortingElement = XmlToArray::getChildByName($navigationElement, 'sorting');
        return $sortingElement
            ? filter(merge(
                XmlToArray::getElementConfig($sortingElement, 'defaultSortByColumn'),
                XmlToArray::getElementConfig($sortingElement, 'defaultSortDirection')
            ))
            : [];
    }

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
        $entityConfigElement = XmlToArray::getChildByName($root, 'entityConfig');
        return $entityConfigElement
            ? filter(['label' => $this->convertEntityLabelConfig($entityConfigElement)])
            : [];

    }

    private function convertEntityLabelConfig(\DOMElement $entityConfigElement): array
    {
        $labelElement = XmlToArray::getChildByName($entityConfigElement, 'label');
        return $labelElement
            ? filter(merge(
                XmlToArray::getElementConfig($labelElement, 'singular'),
                XmlToArray::getElementConfig($labelElement, 'plural'),
            ))
            : [];
    }

    private function convertActionsConfig(\DOMElement $root): array
    {
        /*
         * <actions idColumn="name">
         *     <action id="edit" label="Edit" url="*\/*\/edit" idParam="id"/>
         *     <action id="delete" label="Delete" url="*\/*\/delete">
         *         <event on="click"/>
         *     </action>
         *     <action label="Validate" url="admin/dashboard"/>
         * </actions>
         */
        if ($actionsElement = XmlToArray::getChildByName($root, 'actions')) {
            $actionElements = XmlToArray::getChildrenByName($actionsElement, 'action');
            $actions        = filter(map([$this, 'convertActionConfig'], $actionElements));
            return merge(filter(['@idColumn' => $actionsElement->getAttribute('idColumn')]), ['actions' => $actions]);
        } else {
            return [];
        }
    }

    private function convertActionConfig(\DOMElement $actionElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($actionElement, 'id'),
            XmlToArray::getAttributeConfig($actionElement, 'label'),
            XmlToArray::getAttributeConfig($actionElement, 'url'),
            XmlToArray::getAttributeConfig($actionElement, 'idParam'),
            ['events' => $this->convertJsEventsConfig($actionElement)]
        ));
    }

    private function convertJsEventsConfig(\DOMElement $parent): array
    {
        $eventElements = XmlToArray::getChildrenByName($parent, 'event');
        return merge([], ...map(function (\DOMElement $eventElement): array {
            return [$eventElement->getAttribute('on') => []];
        }, $eventElements));
    }

    private function convertMassActionsConfig(\DOMElement $root)
    {
        /*
         * <massActions idColumn="name" idsParam="ids">
         *     <action label="Update" url="*\/massActions/update"/>
         *     <action label="Delete All" url="*\/massActions/delete" requireConfirmation="true"/>
         * </massActions>
         */
        if ($massActionsElement = XmlToArray::getChildByName($root, 'massActions')) {
            $massActionElements = XmlToArray::getChildrenByName($massActionsElement, 'action');
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
            XmlToArray::getAttributeConfig($actionElement, 'id'),
            XmlToArray::getAttributeConfig($actionElement, 'label'),
            XmlToArray::getAttributeConfig($actionElement, 'url'),
            map(function (string $v): bool {
                return $v === 'true';
            }, XmlToArray::getAttributeConfig($actionElement, 'requireConfirmation')),
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
        $buttonsElement = XmlToArray::getChildByName($navigationElement, 'buttons');
        return $buttonsElement
            ? map([$this, 'getButtonConfig'], XmlToArray::getChildrenByName($buttonsElement, 'button'))
            : null;
    }

    private function getExportsConfig(\DOMElement $navigationElement): ?array
    {
        /*
         *     <exports>
         *         <export type="csv" label="Export to CSV" class="Hyva\Admin\Model\Export\Csv" />
         *         <export type="xml" label="Export to XML" template="Hyva\Admin\Model\Export\Xml" />
         *     </exports>
         */
        $exportsElement = XmlToArray::getChildByName($navigationElement, 'exports');
        return $exportsElement
            ? map([$this, 'getExportConfig'], XmlToArray::getChildrenByName($exportsElement, 'export'))
            : null;
    }

    private function getExportConfig(\DOMElement $exportElement): array
    {
        return filter(merge(
            XmlToArray::getAttributeConfig($exportElement, 'type'),
            XmlToArray::getAttributeConfig($exportElement, 'label'),
            XmlToArray::getAttributeConfig($exportElement, 'fileName'),
            XmlToArray::getAttributeConfig($exportElement, 'enabled'),
            XmlToArray::getAttributeConfig($exportElement, 'class'),
            XmlToArray::getAttributeConfig($exportElement, 'sortOrder')
        // refactor: add class attribute as it is defined in xsd
        ));
    }

    private function getButtonConfig(\DOMElement $buttonsElement): ?array
    {
        return merge(
            XmlToArray::getAttributeConfig($buttonsElement, 'id'),
            XmlToArray::getAttributeConfig($buttonsElement, 'label'),
            XmlToArray::getAttributeConfig($buttonsElement, 'template'),
            XmlToArray::getAttributeConfig($buttonsElement, 'url'),
            XmlToArray::getAttributeConfig($buttonsElement, 'onclick'),
            XmlToArray::getAttributeConfig($buttonsElement, 'sortOrder'),
            XmlToArray::getAttributeConfig($buttonsElement, 'enabled'),
        );
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
        $filtersElement = XmlToArray::getChildByName($navigationElement, 'filters');
        return $filtersElement
            ? map([$this, 'getFilterConfig'], XmlToArray::getChildrenByName($filtersElement, 'filter'))
            : null;
    }

    private function getFilterConfig(\DOMElement $filterElement): ?array
    {
        return merge(
            ['key' => XmlToArray::getAttributeConfig($filterElement, 'column')['column'] ?? null],
            XmlToArray::getAttributeConfig($filterElement, 'enabled'),
            XmlToArray::getAttributeConfig($filterElement, 'template'),
            XmlToArray::getAttributeConfig($filterElement, 'filterType'),
            XmlToArray::getAttributeConfig($filterElement, 'source'),
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
        }, XmlToArray::getChildrenByName($filterElement, 'option')));
        return $options
            ? ['options' => $options]
            : [];
    }

    private function getFilterOptionValuesConfig(\DOMElement $optionElement): array
    {
        return map(function (\DOMElement $valueElement): string {
            return trim($valueElement->nodeValue);
        }, XmlToArray::getChildrenByName($optionElement, 'value'));
    }
}
