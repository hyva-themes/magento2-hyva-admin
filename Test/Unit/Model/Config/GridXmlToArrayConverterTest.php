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
            'array-source-with-type' => [
                $this->getSourceWithTypeArrayXml(),
                $this->getSourceWithTypeArrayExpected(),
            ],
            'array-source-no-type'   => [
                $this->getSourceNoTypeArrayProviderXml(),
                $this->getSourceNoTypeArrayProviderExpected(),
            ],
            'repository-source'      => [
                $this->getRepositorySourceXml(),
                $this->getRepositorySourceExpected(),
            ],
            'collection-source'      => [
                $this->getCollectionSourceXml(),
                $this->getCollectionSourceExpected(),
            ],
            'query-source'           => [
                $this->getQuerySourceXml(),
                $this->getQuerySourceExpected(),
            ],
            'columns'                => [
                $this->getColumnsXml(),
                $this->getColumnsExpected(),
            ],
            'navigation'                => [
                $this->getNavigationXml(),
                $this->getNavigationExpected(),
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
    <columns>
        <include>
            <column name="id"/>
            <column name="note" type="text"/>
            <column name="name" renderer="My\NameRendererBlock"/>
            <column name="speed" label="km/h"/>
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
                'include' => [
                    ['name' => 'id'],
                    ['name' => 'note', 'type' => 'text'],
                    ['name' => 'name', 'renderer' => 'My\NameRendererBlock'],
                    ['name' => 'speed', 'label' => 'km/h'],
                    ['name' => 'color', 'source' => 'My\SourceModel'],
                    ['name' => 'background_color', 'options' => $options],
                ],
                'exclude' => ['reference_id', 'internal_stuff'],
            ],
        ];
    }

    private function getNavigationXml(): string
    {
        return <<<EOXML
    <navigation>
        <pager>
            <defaultPerPage>10</defaultPerPage>
            <pageSizes>10,20,50,100</pageSizes>
        </pager>
    </navigation>
EOXML;
    }

    private function getNavigationExpected(): array
    {
        return ['navigation' => ['pager' => ['defaultPerPage' => '10', 'pageSizes' => '10,20,50,100']]];
    }
}
