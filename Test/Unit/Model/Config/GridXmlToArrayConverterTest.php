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
            'keep-columns-from-source' => [
                $this->getIncludeWithKeepColumnsFromSourceXml(),
                $this->getIncludeWithKeepColumnsFromSourceExpected(),
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
        <query>
            <select>
                <column name="id"/>
                <column name="name"/>
                <column name="t.speed"/>
            </select>
            <from table="fossa_table" as="foo"/>
            <join type="left" table="other_table" alias="t">
                <condition>foo.id=t.id</condition>
            </join>
            <where>
                <and>fossa.product_id IN(:product_ids)</and>
            </where>
        </query>
        <bindParamenters>
            <product_ids>product_ids</product_ids>
        </bindParamenters>
    </source>
EOXML;
    }

    private function getQuerySourceExpected(): array
    {
        return [
            'source' => [
                'query'      => [],
                'bindParams' => [],
            ],
        ];
    }

    private function getColumnsXml(): string
    {
        return <<<EOXML
    <columns rowAction="edit">
        <include>
            <column name="id" sortOrder="1"/>
            <column name="note" type="text" template="Module_Name::file.phtml"/>
            <column name="name" rendererBlockName="name-renderer-block"/>
            <column name="speed" label="km/h"/>
            <column name="logo" renderAsUnsecureHtml="true"/>
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
                    ['key' => 'speed', 'label' => 'km/h'],
                    ['key' => 'logo', 'renderAsUnsecureHtml' => 'true'],
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
    <navigation>
        <pager>
            <defaultPageSize>10</defaultPageSize>
            <pageSizes>10,20,50,100</pageSizes>
        </pager>
        <sorting>
            <defaultSortByColumn>id</defaultSortByColumn>
            <defaultSortDirection>asc</defaultSortDirection>
        </sorting>
    </navigation>
EOXML;
    }

    private function getNavigationExpected(): array
    {
        return ['navigation' => [
            'pager' => ['defaultPageSize' => '10', 'pageSizes' => '10,20,50,100'],
            'sorting' => ['defaultSortByColumn' => 'id', 'defaultSortDirection' => 'asc'],
        ]];
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
        <action id="delete" label="Delete" url="*/*/delete"/>
        <action label="Validate" url="admin/dashboard"/>
    </actions>
EOXML;
    }

    private function getActionsExpected(): array
    {
        $actions = [
            ['id' => 'edit', 'label' => 'Edit', 'url' => '*/*/edit', 'idParam' => 'id'],
            ['id' => 'delete', 'label' => 'Delete', 'url' => '*/*/delete'],
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
            ['label' => 'Update', 'url' => '*/massActions/update'],
            ['label' => 'Delete All', 'url' => '*/massActions/delete', 'requireConfirmation' => true],
            ['label' => 'Reindex', 'url' => '*/massActions/reindex'],
        ];
        return ['massActions' => ['@idColumn' => 'id', '@idsParam' => 'ids', 'actions' => $actions]];
    }
}
