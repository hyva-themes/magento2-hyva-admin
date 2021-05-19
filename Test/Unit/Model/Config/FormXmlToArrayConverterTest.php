<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\Config;

use Hyva\Admin\Model\Config\FormXmlToArrayConverter;
use PHPUnit\Framework\TestCase;

class FormXmlToArrayConverterTest extends TestCase
{
    /**
     * @dataProvider conversionXmlProvider
     */
    public function testConversion(string $xml, array $expected): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML("<grid>$xml</grid>");
        $result = (new FormXmlToArrayConverter())->convert($dom);

        $this->assertSame($expected, $result);
    }

    public function conversionXmlProvider(): array
    {
        return [
            'load-method'        => [
                $this->getLoadXml(),
                $this->getLoadExpected(),
            ],
            'save-method'        => [
                $this->getSaveXml(),
                $this->getSaveExpected(),
            ],
            'save-method-simple' => [
                $this->getSaveSimpleXml(),
                $this->getSaveSimpleExpected(),
            ],
            'fields'             => [
                $this->getFieldsXml(),
                $this->getFieldsExpected(),
            ],
            'sections'           => [
                $this->getSectionsXml(),
                $this->getSectionsExpected(),
            ],
            'empty-section-id'   => [
                $this->getEmptySectionIdXml(),
                $this->getEmptySectionIdExpected(),
            ],
            'navigation'         => [
                $this->getNavigationXml(),
                $this->getNavigationExpected(),
            ],
        ];
    }

    private function getLoadXml(): string
    {
        return <<<EOXML
<load method="\Magento\Customer\Api\CustomerRepositoryInterface::getById"
      type="\Magento\Customer\Api\Data\CustomerInterface">
    <bindArguments>
        <argument name="customerId" requestParam="id"/>
        <argument name="bar" value="BAR"/>
        <argument name="foo" method="\My\Module\Model\Bar::getBaz" param="qux" property="quux"/>
    </bindArguments>
</load>
EOXML;
    }

    private function getLoadExpected(): array
    {
        return [
            'load' => [
                'method'        => '\Magento\Customer\Api\CustomerRepositoryInterface::getById',
                'type'          => '\Magento\Customer\Api\Data\CustomerInterface',
                'bindArguments' => [
                    'customerId' => ['requestParam' => 'id'],
                    'bar'        => ['value' => 'BAR'],
                    'foo'        => [
                        'method'   => '\My\Module\Model\Bar::getBaz',
                        'param'    => 'qux',
                        'property' => 'quux',
                    ],
                ],
            ],
        ];
    }

    private function getSaveXml(): string
    {
        return <<<EOXML
<save method="\Magento\Customer\Api\CustomerRepositoryInterface::save">
    <bindArguments>
        <argument name="customer" formData="true"/>
        <argument name="passwordHash" method="\My\Module\Model\CustomerPassword::hash"/>
    </bindArguments>
</save>
EOXML;
    }

    private function getSaveExpected(): array
    {
        return [
            'save' => [
                'method'        => '\Magento\Customer\Api\CustomerRepositoryInterface::save',
                'bindArguments' => [
                    'customer'     => ['formData' => 'true'],
                    'passwordHash' => ['method' => '\My\Module\Model\CustomerPassword::hash'],
                ],
            ],
        ];
    }

    private function getSaveSimpleXml(): string
    {
        return '<save method="\Magento\Cms\Model\ResourceModel\Block::save"/>';
    }

    private function getSaveSimpleExpected(): array
    {
        return ['save' => ['method' => '\Magento\Cms\Model\ResourceModel\Block::save']];
    }

    private function getFieldsXml(): string
    {
        return <<<EOXML
<fields keepAllSourceFields="true">
    <field name="identifier" type="text" group="important-things" pattern=".*" required="true" minlength="1" maxlength="11"/>
    <field name="title" template="My_Module::form/title-field.phtml" renderAsSingleColumn="true"/>
    <field name="content" type="wysiwyg"/>
    <field name="creation_time" type="datetime" min="2020-01-01T00:00:00" max="2030-01-01T00:00:00" step="3600"/>
    <field name="is_active" label="Active?" type="boolean" disabled="false"/>
    <field name="comment" hidden="false" sortOrder="10"/>
    <field name="store_ids" type="select" source="\Magento\Eav\Model\Entity\Attribute\Source\Store"/>
    <field name="admin" valueProcessor="\My\Module\Form\AdminLinkProcessor"/>
</fields>
EOXML;
    }

    private function getFieldsExpected(): array
    {
        return [
            'fields' => [
                '@keepAllSourceFields' => 'true',
                'fields'               => [
                    [
                        'name'      => 'identifier',
                        'type'      => 'text',
                        'groupId'   => 'important-things',
                        'pattern'   => '.*',
                        'required'  => 'true',
                        'minlength' => '1',
                        'maxlength' => '11',
                    ],
                    [
                        'name'                 => 'title',
                        'template'             => 'My_Module::form/title-field.phtml',
                        'renderAsSingleColumn' => 'true',
                    ],
                    ['name' => 'content', 'type' => 'wysiwyg'],
                    [
                        'name' => 'creation_time',
                        'type' => 'datetime',
                        'min'  => '2020-01-01T00:00:00',
                        'max'  => '2030-01-01T00:00:00',
                        'step' => '3600',
                    ],
                    ['name' => 'is_active', 'type' => 'boolean', 'label' => 'Active?', 'disabled' => 'false'],
                    ['name' => 'comment', 'hidden' => 'false', 'sortOrder' => "10"],
                    [
                        'name'   => 'store_ids',
                        'type'   => 'select',
                        'source' => '\Magento\Eav\Model\Entity\Attribute\Source\Store',
                    ],
                    ['name' => 'admin', 'valueProcessor' => '\My\Module\Form\AdminLinkProcessor'],
                ],
            ],
        ];
    }

    private function getSectionsXml(): string
    {
        return <<<EOXML
<sections>
    <section id="foo" label="Foos" sortOrder="10">
        <group id="important-things" sortOrder="10"/>
        <group id="details" sortOrder="20" label="Details"/>
    </section>
    <section id="bar" label="Bars" sortOrder="20">
        <group id="whatever" sortOrder="10"/>
    </section>
</sections>
EOXML;

    }

    private function getSectionsExpected(): array
    {
        return [
            'sections' => [
                [
                    'id'        => 'foo',
                    'label'     => 'Foos',
                    'sortOrder' => '10',
                    'groups'    => [
                        ['id' => 'important-things', 'sortOrder' => '10'],
                        ['id' => 'details', 'label' => 'Details', 'sortOrder' => '20'],
                    ],
                ],
                [
                    'id'        => 'bar',
                    'label'     => 'Bars',
                    'sortOrder' => '20',
                    'groups'    => [
                        ['id' => 'whatever', 'sortOrder' => '10'],
                    ],
                ],
            ],
        ];
    }

    private function getEmptySectionIdXml(): string
    {
        return <<<EOXML
<sections>
    <section id="" label="Default" sortOrder="10">
    </section>
</sections>
EOXML;

    }

    private function getEmptySectionIdExpected(): array
    {
        return [
            'sections' => [
                [
                    'id'        => '',
                    'label'     => 'Default',
                    'sortOrder' => '10',
                ],
            ],
        ];
    }

    private function getNavigationXml(): string
    {
        return <<<EOXML
<navigation>
    <buttons>
        <button id="save" label="Save" url="hyva_admin/form/save" enabled="false" />
        <button id="only-visible-when-entity-loaded" label="Example" hiddenForNewEntity="true"/>
        <button id="reset" label="Reset" url="*/*/*"/>
    </buttons>
</navigation>
EOXML;
    }

    private function getNavigationExpected(): array
    {
        return [
            'navigation' => [
                'buttons' => [
                    ['id' => 'save', 'label' => 'Save', 'url' => 'hyva_admin/form/save', 'enabled' => 'false'],
                    ['id' => 'only-visible-when-entity-loaded', 'label' => 'Example', 'hiddenForNewEntity' => 'true'],
                    ['id' => 'reset', 'label' => 'Reset', 'url' => '*/*/*'],
                ],
            ],
        ];
    }
}
