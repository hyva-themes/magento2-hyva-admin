# grid > navigation > exports

The `<exports>` node is used configure available export types for a grid.

```html
<navigation>
    <exports>
        <export type="csv" label="Export as CSV"/>
        <export type="xml" label="Export as XML" enabled="false"/>
        <export type="xlsx" label="Export as XLSX"/>
        <export type="custom"
                label="Export as my custom format"
                class="My\Module\Model\CustomGridExport"
                fileName="example.foo"
                sortOrder="1"
        />
    </exports>
</navigation>
```

Each available type is declared as a child `<export>` node.

Each export is identified by its type. Three types are available out of the box (csv, xml and xlsx).

Further information about implementing custom export types can be found in the PHP API reference under [Grid AbstractExportType](../../../php-classes-and-interfaces/grid-abstractexporttype.md).

## Attributes

The `<exports>` node has no attributes, but the child <export> several:

### type (required)

The `type` attribute identifies each configured export. The value is a short string identifier that also usually works as a file name suffix for the export file.

### label

The `label` for the export link. If it isn’t specified, it defaults to

`'Export as ' . mb_strtoupper($this->getType())`.

### enabled

The `enabled` attribute can be used to disable export types that are declared elsewhere.

### class

The name of the PHP class to generate the export file. Useful only for custom export types.

It can also be used to override a default export type implementation.

### fileName

The name of the export file, relative to the export types export dir. Files in subdirectories are supported (e.g. `export/export.foo`). If not specified, the grid name plus the export type as the file name suffix is used.

### sortOrder

By default exports are rendered in the order they are found in the configuration.

The order can be change with the `sortOrder` attribute. Export types with a `sortOrder` will be rendered before export types without a `sortOrder` attribute.

