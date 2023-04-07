<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\Config;

use Hyva\Admin\Model\Config\GridXmlToArrayConverter;
use PHPUnit\Framework\TestCase;

class GridXmlToArrayConverterTest extends TestCase
{
    /**
     * @dataProvider conversionXmlProvider
     */
    public function testConversion(string $xml, array $expected): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML("<grid>$xml</grid>");
        $result = (new GridXmlToArrayConverter())->convert($dom);

        $this->assertSame($expected, $result);
    }

    public function conversionXmlProvider(): array
    {
        return [
            'array-source-with-type'   => [
                $this->getSourceWithTypeArrayXml(),
                $this->getSourceWithTypeArrayExpected(),
            ],
            'array-source-no-type'     => [
                $this->getSourceNoTypeArrayProviderXml(),
                $this->getSourceNoTypeArrayProviderExpected(),
            ],
            'repository-source'        => [
                $this->getRepositorySourceXml(),
                $this->getRepositorySourceExpected(),
            ],
            'collection-source'        => [
                $this->getCollectionSourceXml(),
                $this->getCollectionSourceExpected(),
            ],
            'query-source'             => [
                $this->getQuerySourceXml(),
                $this->getQuerySourceExpected(),
            ],
            'source-with-processors' => [
                $this->getSourceProcessorsXml(),
                $this->getSourceProcessorsExpected(),
            ],
            'columns'                  => [
                $this->getColumnsXml(),
                $this->getColumnsExpected(),
            ],
            'empty-columns'            => [
                $this->getEmptyColumnsXml(),
                $this->getEmptyColumnsExpected(),
            ],
            'exclude'                  => [
                $this->getOnlyColumnsExcludeXml(),
                $this->getOnlyColumnsExcludeExpected(),
            ],
            'include'                  => [
                $this->getOnlyColumnsIncludeXml(),
                $this->getOnlyColumnsIncludeExpected(),
            ],
            'navigation'               => [
                $this->getNavigationXml(),
                $this->getNavigationExpected(),
            ],
            'entity'                   => [
                $this->getEntityConfigXml(),
                $this->getEntityConfigExpected(),
            ],
            'actions'                  => [
                $this->getActionsXml(),
                $this->getActionsExpected(),
            ],
            'empty-actions'            => [
                $this->getEmptyActionsXml(),
                $this->getEmptyActionsExpected(),
            ],
            'mass-actions'             => [
                $this->getMassActionXml(),
                $this->getMassActionExpected(),
            ],
            'exports'                  => [
                $this->getExportXml(),
                $this->getExportExpected(),
            ],
            'keep-columns-from-source' => [
                $this->getIncludeWithKeepColumnsFromSourceXml(),
                $this->getIncludeWithKeepColumnsFromSourceExpected(),
            ],
            'filters'                  => [
                $this->getFiltersXml(),
                $this->getFiltersExpected(),
            ],
            'bindparams'               => [
                $this->getSearchCriteriaBindParamsXml(),
                $this->getSearchCriteriaBindParamsExpected(),
            ],
        ];
    }

    private function getSourceWithTypeArrayXml(): string
    {
        return <<<EOXML
    <source type="array">
        <arrayProvider>ArrayProviderInterface</arrayProvider>
    </source>
EOXML;
    }

    private function getSourceWithTypeArrayExpected(): array
    {
        return [
            'source' => [
                '@type'         => 'array',
                'arrayProvider' => 'ArrayProviderInterface',
            ],
        ];
    }

    private function getSourceNoTypeArrayProviderXml(): string
    {
        return <<<EOXML
    <source>
        <arrayProvider>ArrayProviderInterface</arrayProvider>
    </source>
EOXML;
    }

    private function getSourceNoTypeArrayProviderExpected(): array
    {
        return ['source' => ['arrayProvider' => 'ArrayProviderInterface']];
    }

    private function getRepositorySourceXml(): string
    {
        return <<<EOXML
    <source>
        <repositoryListMethod>Repository::getList</repositoryListMethod>
    </source>
EOXML;
    }

    private function getRepositorySourceExpected(): array
    {
        return ['source' => ['repositoryListMethod' => 'Repository::getList']];
    }

    private function getCollectionSourceXml(): string
    {
        return <<<EOXML
    <source>
        <collection>SomeCollectionClass</collection>
    </source>
EOXML;
    }

    private function getCollectionSourceExpected(): array
    {
        return ['source' => ['collection' => 'SomeCollectionClass']];
    }

    private function getQuerySourceXml(): string
    {
        return <<<EOXML
    <source>
        <query unionSelectType="distinct">
            <select>
                <from table="catalog_product" as="main_table"/>
                <columns>
                    <column name="entity_id" as="id"/>
                    <column name="sku"/>
                    <expression as="count">COUNT(*)</expression>
                </columns>
                <join type="left" table="catalog_product_entity_varchar" as="t_name">
                    <on>t_name.entity_id=main_table.entity_id AND attribute_id=47</on>
                    <columns>
                        <column name="value" as="name"/>
                    </columns>
                </join>
                <groupBy>
                    <column name="main_table.attribute_set_id"/>
                    <column name="t_name.name"/>
                </groupBy>
            </select>
            <unionSelect>
                <from table="catalog_product_other"/>
             </unionSelect>
        </query>
    </source>
EOXML;
    }

    private function getQuerySourceExpected(): array
    {
        return [
            'source' => [
                'query' => [
                    '@unionSelectType' => 'distinct',
                    'select'           => [
                        'from'    => ['table' => 'catalog_product', '@as' => 'main_table'],
                        'columns' => [
                            ['column' => 'entity_id', '@as' => 'id'],
                            ['column' => 'sku'],
                            ['expression' => 'COUNT(*)', '@as' => 'count'],
                        ],
                        'joins'   => [
                            [
                                'type'    => 'left',
                                'join'    => ['table' => 'catalog_product_entity_varchar', '@as' => 't_name'],
                                'on'      => 't_name.entity_id=main_table.entity_id AND attribute_id=47',
                                'columns' => [
                                    ['column' => 'value', '@as' => 'name'],
                                ],
                            ],
                        ],
                        'groupBy' => [
                            ['column' => 'main_table.attribute_set_id'],
                            ['column' => 't_name.name'],
                        ],
                    ],
                    'unions'           => [
                        [
                            'from' => ['table' => 'catalog_product_other'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getSourceProcessorsXml(): string
    {
        return <<<EOXML
    <source>
        <processors>
            <processor class="\Hyva\Aaaa\Model\GridProcessorA" enabled="true"/>
            <processor class="\Hyva\Bbbb\Model\GridProcessorB" enabled="false"/>
            <processor class="\Hyva\Cccc\Model\GridProcessorC"/>
        </processors>
    </source>
EOXML;
    }

    private function getSourceProcessorsExpected(): array
    {
        return ['source' => [
            'processors' => [
                ['class' => '\Hyva\Aaaa\Model\GridProcessorA', 'enabled' => 'true'],
                ['class' => '\Hyva\Bbbb\Model\GridProcessorB', 'enabled' => 'false'],
                ['class' => '\Hyva\Cccc\Model\GridProcessorC']
            ]
        ]];
    }

    private function getColumnsXml(): string
    {
        return <<<EOXML
    <columns rowAction="edit">
        <include>
            <column name="id" sortOrder="1"/>
            <column name="note" type="text" template="Module_Name::file.phtml"/>
            <column name="name" rendererBlockName="name-renderer-block"/>
            <column name="speed" label="km/h" initiallyHidden="true"/>
            <column name="logo" renderAsUnsecureHtml="true" sortable="false"/>
            <column name="color" source="My\SourceModel"/>
            <column name="background_color">
                <option value="1" label="red"/>
                <option value="5" label="blue"/>
                <option value="3" label="black"/>
            </column>
        </include>
        <exclude>
            <column name="reference_id"/>
            <column name="internal_stuff"/>
        </exclude>
    </columns>
EOXML;
    }

    private function getColumnsExpected(): array
    {
        $options = [
            ['value' => '1', 'label' => 'red'],
            ['value' => '5', 'label' => 'blue'],
            ['value' => '3', 'label' => 'black'],
        ];
        return [
            'columns' => [
                '@rowAction' => 'edit',
                'include'    => [
                    ['key' => 'id', 'sortOrder' => '1'],
                    ['key' => 'note', 'type' => 'text', 'template' => 'Module_Name::file.phtml'],
                    ['key' => 'name', 'rendererBlockName' => 'name-renderer-block'],
                    ['key' => 'speed', 'label' => 'km/h', 'initiallyHidden' => 'true'],
                    ['key' => 'logo', 'renderAsUnsecureHtml' => 'true', 'sortable' => 'false'],
                    ['key' => 'color', 'source' => 'My\SourceModel'],
                    ['key' => 'background_color', 'options' => $options],
                ],
                'exclude'    => ['reference_id', 'internal_stuff'],
            ],
        ];
    }

    private function getOnlyColumnsExcludeXml(): string
    {
        return <<<EOXML
        <columns>
            <exclude>
                <column name="weight"/>
            </exclude>
        </columns>
EOXML;
    }

    private function getOnlyColumnsExcludeExpected(): array
    {
        return [
            'columns' => [
                'exclude' => ['weight'],
            ],
        ];
    }

    private function getOnlyColumnsIncludeXml(): string
    {
        return <<<EOXML
        <columns>
            <include>
                <column name="weight"/>
            </include>
        </columns>
EOXML;
    }

    private function getOnlyColumnsIncludeExpected(): array
    {
        return [
            'columns' => [
                'include' => [['key' => 'weight']],
            ],
        ];
    }

    private function getIncludeWithKeepColumnsFromSourceXml(): string
    {
        return <<<EOXML
        <columns>
            <include keepAllSourceColumns="true">
                <column name="weight"/>
            </include>
        </columns>
EOXML;
    }

    private function getIncludeWithKeepColumnsFromSourceExpected()
    {
        return [
            'columns' => [
                '@keepAllSourceCols' => "true",
                'include'            => [['key' => 'weight']],
            ],
        ];
    }

    private function getEmptyColumnsXml(): string
    {
        return <<<EOXML
        <columns>
        </columns>
EOXML;
    }

    private function getEmptyColumnsExpected(): array
    {
        return [];
    }

    private function getNavigationXml(): string
    {
        return <<<EOXML
    <navigation useAjax="true">
        <pager enabled="false">
            <defaultPageSize>10</defaultPageSize>
            <pageSizes>10,20,50,100</pageSizes>
        </pager>
        <sorting>
            <defaultSortByColumn>id</defaultSortByColumn>
            <defaultSortDirection>asc</defaultSortDirection>
        </sorting>
        <filters>
            <filter column="id"/>
        </filters>
        <buttons>
            <button id="add" label="Add" url="*/*/add" enabled="false"/>
            <button id="foo" label="Foo" onclick="doFoo" sortOrder="123"/>
            <button id="bar" template="Module_Name::button.phtml"/>
        </buttons>
    </navigation>
EOXML;
    }

    private function getNavigationExpected(): array
    {
        return [
            'navigation' => [
                '@isAjaxEnabled' => 'true',
                'pager'          => ['@enabled' => 'false', 'defaultPageSize' => '10', 'pageSizes' => '10,20,50,100'],
                'sorting'        => ['defaultSortByColumn' => 'id', 'defaultSortDirection' => 'asc'],
                'filters'        => [
                    ['key' => 'id'],
                ],
                'buttons'        => [
                    ['id' => 'add', 'label' => 'Add', 'url' => '*/*/add', 'enabled' => 'false'],
                    ['id' => 'foo', 'label' => 'Foo', 'onclick' => 'doFoo', 'sortOrder' => '123'],
                    ['id' => 'bar', 'template' => 'Module_Name::button.phtml'],
                ],
            ],
        ];
    }

    private function getEntityConfigXml(): string
    {
        return <<<EOXML
    <entityConfig>
        <label>
            <singular>Fossa</singular>
            <plural>Fossas</plural>
        </label>
    </entityConfig>
EOXML;
    }

    private function getEntityConfigExpected(): array
    {
        return ['entity' => ['label' => ['singular' => 'Fossa', 'plural' => 'Fossas']]];
    }

    private function getActionsXml(): string
    {
        return <<<EOXML
    <actions idColumn="name">
        <action id="edit" label="Edit" url="*/*/edit" idParam="id"/>
        <action id="delete" label="Delete" url="*/*/delete">
            <event on="click"/>
        </action>
        <action label="Validate" url="admin/dashboard"/>
    </actions>
EOXML;
    }

    private function getActionsExpected(): array
    {
        $actions = [
            ['id' => 'edit', 'label' => 'Edit', 'url' => '*/*/edit', 'idParam' => 'id'],
            ['id' => 'delete', 'label' => 'Delete', 'url' => '*/*/delete', 'events' => ['click' => []]],
            ['label' => 'Validate', 'url' => 'admin/dashboard'],
        ];
        return ['actions' => ['@idColumn' => 'name', 'actions' => $actions]];
    }

    private function getEmptyActionsXml(): string
    {
        return <<<EOXML
    <actions idColumn="name">
    </actions>
EOXML;
    }

    private function getEmptyActionsExpected(): array
    {
        return ['actions' => ['@idColumn' => 'name', 'actions' => []]];
    }

    private function getMassActionXml(): string
    {
        return <<<EOXML
    <massActions idColumn="id" idsParam="ids">
        <action id="update" label="Update" url="*/massActions/update"/>
        <action id="delete" label="Delete All" url="*/massActions/delete" requireConfirmation="true"/>
        <action id="reindex" label="Reindex" url="*/massActions/reindex" />
    </massActions>
EOXML;
    }

    private function getMassActionExpected(): array
    {
        $actions = [
            ['id' => 'update', 'label' => 'Update', 'url' => '*/massActions/update'],
            ['id' => 'delete', 'label' => 'Delete All', 'url' => '*/massActions/delete', 'requireConfirmation' => true],
            ['id' => 'reindex', 'label' => 'Reindex', 'url' => '*/massActions/reindex'],
        ];
        return ['massActions' => ['@idColumn' => 'id', '@idsParam' => 'ids', 'actions' => $actions]];
    }

    private function getExportXml(): string
    {
        return <<<EOXML
    <navigation>
        <exports>
            <export type="csv" label="Export to CSV"/>
            <export type="xml" label="Export to XML" class="\My\Custom\Export" />
            <export type="csv2" label="Export to CSV2" fileName="file.csv" enabled="true" sortOrder="1"/>
        </exports>
    </navigation>
EOXML;
    }

    private function getExportExpected(): array
    {
        return [
            'navigation' => [
                'exports' => [
                    ['type' => 'csv', 'label' => 'Export to CSV'],
                    ['type' => 'xml', 'label' => 'Export to XML', 'class' => '\My\Custom\Export'],
                    [
                        'type'      => 'csv2',
                        'label'     => 'Export to CSV2',
                        'fileName'  => 'file.csv',
                        'enabled'   => 'true',
                        'sortOrder' => '1',
                    ],
                ],
            ],
        ];
    }

    private function getFiltersXml(): string
    {
        return <<<EOXML
    <navigation>
        <filters>
            <filter column="sku" enabled="true" template="Foo_Bar::filter.phtml"/>
            <filter column="created_at"/>
            <filter column="id" filterType="Foo\Bar\Model\GridFilter\Baz"/>
            <filter column="color">
                <option label="reddish">
                    <value>16</value>
                    <value>17</value>
                    <value>18</value>
                </option>
                <option label="blueish">
                    <value>12</value>
                </option>
                <option label="rose">
                    <value>100</value>
                </option>
                <option label="empty">
                </option>
            </filter>
            <filter column="store_id" source="\Magento\Config\Model\Config\Source\Store"/>
        </filters>
    </navigation>
EOXML;
    }

    private function getFiltersExpected(): array
    {
        return [
            'navigation' => [
                'filters' => [
                    ['key' => 'sku', 'enabled' => 'true', 'template' => 'Foo_Bar::filter.phtml'],
                    ['key' => 'created_at'],
                    ['key' => 'id', 'filterType' => 'Foo\Bar\Model\GridFilter\Baz'],
                    [
                        'key'     => 'color',
                        'options' => [
                            ['label' => 'reddish', 'values' => ['16', '17', '18']],
                            ['label' => 'blueish', 'values' => ['12']],
                            ['label' => 'rose', 'values' => ['100']],
                        ],
                    ],
                    ['key' => 'store_id', 'source' => '\Magento\Config\Model\Config\Source\Store'],
                ],
            ],
        ];
    }

    private function getSearchCriteriaBindParamsXml(): string
    {
        return <<<EOXML
    <source type="array">
        <arrayProvider>ArrayProviderInterface</arrayProvider>
        <defaultSearchCriteriaBindings combineConditionsWith="or">
            <field name="my_id" requestParam="id"/>
            <field name="entity_id" method="Magento\Framework\App\RequestInterface::getParam" param="id"/>
            <field name="store_id" method="Magento\Store\Model\StoreManagerInterface::getStore" property="id"/>
            <field name="customer_ids" condition="finset" method="Magento\Customer\Model\Session::getCustomerId"/>
            <field name="foo" condition="neq" value="FOO"/>
        </defaultSearchCriteriaBindings>
    </source>
EOXML;
    }

    private function getSearchCriteriaBindParamsExpected(): array
    {
        return [
            'source' => [
                '@type'                         => 'array',
                'arrayProvider'                 => 'ArrayProviderInterface',
                'defaultSearchCriteriaBindings' => [
                    'combineConditionsWith' => 'or',
                    'fields' => [
                        ['field' => 'my_id', 'requestParam' => 'id'],
                        [
                            'field'  => 'entity_id',
                            'method' => 'Magento\Framework\App\RequestInterface::getParam',
                            'param'  => 'id',
                        ],
                        [
                            'field'    => 'store_id',
                            'method'   => 'Magento\Store\Model\StoreManagerInterface::getStore',
                            'property' => 'id',
                        ],
                        [
                            'field'     => 'customer_ids',
                            'method'    => 'Magento\Customer\Model\Session::getCustomerId',
                            'condition' => 'finset',
                        ],
                        ['field' => 'foo', 'condition' => 'neq', 'value' => 'FOO'],
                    ]
                ],
            ],
        ];
    }
}
