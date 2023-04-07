# Quickstart Introduction


Once installed, grids can be added to any admin page by adding a bit of layout XML and a grid configuration file.

The layout XML has to contain two things:

* A `<update handle="hyva_admin_grid"/>` declaration to load alpine.js and tailwind.
* A `Hyva\Admin\Block\Adminhtml\HyvaGrid` block, with the name of the grid configuration as a the block name or a `grid_name` block argument or as the blocks name-in-layout.

An example file `view/adminhtml/layout/example.xml` could look as follows:

```html
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="hyva_admin_grid"/>
    <body>
        <referenceContainer name="content">
            <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="example-grid"/>
        </referenceContainer>
    </body>
</page>
```

After that, a grid configuration has to be created in a directory `[Your_Module]/view/adminhtml/hyva-grid`, where the file names corresponds to the name that was passed to the grid block (with a `.xml` suffix added to the file name). For the example above it would be

`view/adminhtml/hyva-grid/example-grid.xml`.

When writing the grid configuration, any good IDE will allow for auto-completion and validation of the XML thanks to the XSD schema found in the Hyva_Admin module at `etc/hyva-grid.xsd`.

The grid configuration will need contain a grid source specification. Currently that can be a repository list method, or a`\Hyva\Admin\Api\HyvaGridArrayProviderInterface` implementation.

With no further configuration, all fields of the provided records are shown as grid columns.

It's then possible to either exclude columns as needed, or, alternatively, specify an include-list for the columns to display.

In many cases the default will be good enough and no further configuration beyond the grid source will be necessary.

Grid row actions, mass actions, paging and filtering can also be configured as needed.

If this quick start was a bit too terse, check out the [walkthrough](../hyva-admin-grid-walkthrough/prerequisites-for-a-grid.md).

Also, maybe have a look at the Examples documentation and the Grid XML reference for more information.
