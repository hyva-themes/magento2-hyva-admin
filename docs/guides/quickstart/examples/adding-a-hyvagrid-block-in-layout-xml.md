# Adding a HyvaGrid Block in Layout XML

The following is all the layout XML that is required to show a Hyva admin grid on an admin page.

1. The `<update>` handle is needed to load alpine.js and tailwind.
2. The HyvaGrid block with the grid_name arguement is needed to load the grid.

The grid configuration then is read from `view/adminhtml/hyva-grid/some-grid.xml`.

```html
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="hyva_admin_grid"/>
    <body>
        <referenceContainer name="content">
            <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="some-grid"/>
        </referenceContainer>
    </body>
</page>
```

The grid name can also be specified with a block argument. The following example is equivalent with the one above:

```html
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="hyva_admin_grid"/>
    <body>
        <referenceContainer name="content">
            <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="demo-grid">
                <arguments>
                    <argument name="grid_name" xsi:type="string">some-grid</argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```

