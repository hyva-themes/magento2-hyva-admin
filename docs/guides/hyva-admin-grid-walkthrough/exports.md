# Exports

Exports allow admin users to export the current selection of the grid.

Filters and sort orders are applied when exporting a grid.

Three export types are supported out of the box, and it also is possible to create custom export formats.

#### Supported Export Formats:

* CSV
* XML
* XLSX

#### Example:

```html
<navigation>
    <exports>
        <export type="csv" label="Export as CSV"/>
        <export type="xml" label="Export as XML" enabled="false"/>
        <export type="xlsx" label="Export as XLSX"/>
        <export type="custom"
                label="Export as my custom format"
                class="MyModuleModelCustomGridExport"
                fileName="example.foo"
                sortOrder="1"
        />
    </exports>
</navigation>
```

For the known export types `csv`, `xml` and `xlsx` no `class` declaration is necessary.

For custom types an export class needs to be specified. The custom export class has to extend from

`Hyva\Admin\Model\Grid\ExportType\AbstractExportType`.and implement the method

`public function createFileToDownload()`.

More information about custom export types can be found in the PHP API reference at [Grid AbstractExportType](../../api-reference/php-classes-and-interfaces/grid-abstractexporttype.md).

More information about the export XML configuration can be found in the XML API reference at [grid > navigation > exports](../../api-reference/grid-xml-reference/navigation/exports/index.md).

