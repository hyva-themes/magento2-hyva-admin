# Array Grid Provider Configuration

This is how a grid with an array provider source can be configured.
Technically not even an exclude column is required - but leaving only the source config in the example seemed like too little.

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <arrayProvider>Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
    </source>
    <columns>
        <exclude>
            <column name="leaf"/>
        </exclude>
    </columns>
</grid>
```

