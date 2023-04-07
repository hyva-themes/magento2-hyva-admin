# Repository::getList Grid Data Provider

This is an example for a grid configuration that uses the product repository as a data source.

Repositories are the most common type of grid data providers, since they often already exist in the core or custom modules.

The configuration can be as simple as the previous minimalist array provider example, but the example below showcases more grid configuration possibilities.

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <repositoryListMethod>\Magento\Catalog\Api\ProductRepositoryInterface</repositoryListMethod>
    </source>
    <columns>
        <include>
            <column name="id"/>
            <column name="sku"/>
            <column name="activity"/>
            <column name="name"/>
            <column name="image" type="magento_product_image" renderAsUnsecureHtml="true"
                    label="Main Image"
                    template="Hyva_AdminTest::image.phtml"/>
            <column name="media_gallery" renderAsUnsecureHtml="true"/>
            <column name="price" type="price"/>
            <column name="short_description" initiallyHidden="true"/>
        </include>
        <exclude>
            <column name="category_gear"/>
        </exclude>
    </columns>
    <actions idColumn="id">
        <action id="edit" label="Edit" url="*/*/edit"/>
        <action id="delete" label="Delete" url="*/*/delete"/>
    </actions>
    <massActions idColumn="id">
        <action id="reindex" label="Reindex" url="*/massAction/reindex"/>
        <action id="delete" label="Delete" url="*/massAction/delete"
                requireConfirmation="true"/>
    </massActions>
    <navigation>
        <pager>
            <defaultPageSize>5</defaultPageSize>
            <pageSizes>2,5,10</pageSizes>
        </pager>
        <sorting>
            <defaultSortByColumn>sku</defaultSortByColumn>
            <defaultSortDirection>desc</defaultSortDirection>
        </sorting>
        <filters>
            <filter column="sku"/>
            <filter column="category_ids"/>
            <filter column="id"/>
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
            </filter>
        </filters>
    </navigation>
</grid>
```

