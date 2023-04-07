# Declaring the Grid Block

The first step in creating a Hyvä grid is to declare the grid block in layout XML and load alpine.js and tailwind.

To load alpine.js and tailwind, use an update layout directive for the handle `hyva_admin_grid`:

```html
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">

    <update handle="hyva_admin_grid"/>

</page>
```

The block usually is added to the content area container, and has to be a `Hyva\Admin\Block\Adminhtml\HyvaGrid` instance.

Specify the grid name as the block name, or as a block argument `grid_name`.

```html
<referenceContainer name="content">
    <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="demo-grid"/>
</referenceContainer>
```

This is what it looks like to specify the block name as an argument:

```html
<referenceContainer name="content">
    <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="walkthrough-demo-grid">
        <arguments>
            <argument name="grid_name" xsi:type="string">demo-grid</argument>
        </arguments>
   </block>
</referenceContainer>
```

The name of the grid refers to the grid XML file name (without the .xml filename ending).

For the above examples it would be `[Module_Dir]/view/adminhtml/hyva-grid/demo-grid.xml`.

