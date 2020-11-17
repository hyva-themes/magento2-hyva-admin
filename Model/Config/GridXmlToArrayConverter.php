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
            'source'     => $this->convertSourceConfig($root),
            'columns'    => $this->convertColumnsConfig($root),
            'navigation' => $this->convertNavigationConfig($root),
            'entity'     => $this->convertEntityConfig($root),
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
                $this->getQuerySourceConfig($sourceElement)
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
    private function getAttributeConfig(\DOMElement $element, string $name): array
    {
        $value = $element->getAttribute($name);
        return $value !== ''
            ? [$name => $value]
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
         * <columns>
         *     <include>
         *         <column name="id"/>
         *         <column name="note" type="text"/>
         *         <column name="name" renderer="My\NameRendererBlock"/>
         *         <column name="speed" label="km/h"/>
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
        return merge(
            $this->getColumnsIncludeConfig($columnsElement),
            $this->getColumnsExcludeConfig($columnsElement),
        );
    }

    private function getColumnsIncludeConfig(\DOMElement $columnsElement): array
    {
        $includesElement = $this->getChildByName($columnsElement, 'include');
        $columns         = map([$this, 'buildIncludeColumnConfig'],
            $this->getChildrenByName($includesElement, 'column'));
        return ['include' => $columns];
    }

    private function buildIncludeColumnConfig(\DOMElement $columnElement): array
    {
        return filter(merge(
            $this->getAttributeConfig($columnElement, 'name'),
            $this->getAttributeConfig($columnElement, 'type'),
            $this->getAttributeConfig($columnElement, 'renderer'),
            $this->getAttributeConfig($columnElement, 'label'),
            $this->getAttributeConfig($columnElement, 'source'),
            $this->getOptionsConfig($columnElement),
        ));
    }

    private function getOptionsConfig(\DOMElement $columnElement): array
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
         * <navigation>
         *     <pager>
         *         <defaultPerPage>10</defaultPerPage>
         *         <pageSizes>10,20,50,100</pageSizes>
         *     </pager>
         * </navigation>
         */
        $navigationElement = $this->getChildByName($root, 'navigation');
        return $navigationElement
            ? filter(['pager' => $this->getPagerConfig($navigationElement)])
            : [];
    }

    private function getPagerConfig(\DOMElement $navigationElement): array
    {
        $pagerElement = $this->getChildByName($navigationElement, 'pager');
        return $pagerElement
            ? filter(merge(
                $this->getElementConfig($pagerElement, 'defaultPerPage'),
                $this->getElementConfig($pagerElement, 'pageSizes')
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
}
