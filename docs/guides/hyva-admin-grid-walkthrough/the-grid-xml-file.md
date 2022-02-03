# The Grid XML file

Grid XML files are located in the directory `view/adminhtml/hyva-grid`.

A skeleton grid XML file looks like this:

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">

</grid>
```

The grid XML file is named after the HyvaGrid block name or the `grid_name` layout XML argument on the block.

For example, if the `HyvaGrid` block `grid_name` layout XML argument is `my-first-grid`, then the grid XML file name would be `view/adminhtml/hyva-grid/my-first-grid.xml`.

```html
<arguments>
    <argument name="grid_name" xsi:type="string">my-first-grid</argument>
</arguments>
```

The XML Schema declaration on the `<grid>` root element isn’t needed, but it’s a good idea to include it so IDEs like PHPStorm can offer autocompletion and validation. That makes writing the grid definition a breeze.

```html
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
```

The XSD schema can be found in the Hyva_Admin module at `etc/hyva-grid.xsd`.

Grid XML is merged on a per-grid bases like any other Magento XML file type. In this way it is possible to extend or customize grids declared in other modules.
