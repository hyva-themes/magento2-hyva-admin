# Grid AbstractExportType

`Hyva\Admin\Model\GridExport\Type\AbstractExportType` is the base class for all grid export format implementations.

At the time of writing, there are three built in export types:

* CSV - `Hyva\Admin\Model\GridExport\Type\Csv`
* XML - `Hyva\Admin\Model\GridExport\Type\Xml`
* XLSX - `Hyva\Admin\Model\GridExport\Type\Xlsx`

Custom export types can be created by extending the `AbstractExportType` and setting the `class` attribute on the grid XML export configuration.

Alternatively, the mapping from export type code to class can be extended via `di.xml` like the default types.

```html
<type name="Hyva\Admin\Model\GridExport\GridExportTypeLocator">
    <arguments>
        <argument name="gridExportTypes" xsi:type="array">
            <item name="csv" xsi:type="string">Hyva\Admin\Model\GridExport\Type\Csv</item>
            <item name="xml" xsi:type="string">Hyva\Admin\Model\GridExport\Type\Xml</item>
            <item name="xlsx" xsi:type="string">Hyva\Admin\Model\GridExport\Type\Xlsx</item>
        </argument>
    </arguments>
</type>
```

In this case no `class` attribute needs to be specified in the grid export configuration.

## Abstract methods:

#### public function createFileToDownload(): void

When called this method should create the file to be exported on the filesystem with the export data.

The file will automatically be deleted after the contents are sent to the browser.

The CSV export class can serve as a simple implementation reference:

```php
public function createFileToDownload(): void
{
    $file      = $this->getFileName();
    $directory = $this->filesystem->getDirectoryWrite($this->getExportDir());
    $stream    = $directory->openFile($file, 'w+');
    $stream->lock();
    $stream->writeCsv($this->getHeaderData());
    foreach ($this->iterateGrid() as $row) {
        $stream->writeCsv(map(function (CellInterface $cell): string {
            return $cell->getTextValue();
        }, $row->getCells()));
    }
    $stream->unlock();
    $stream->close();
}
```

## Parent class methods:

There are a number of methods provided by the abstract parent class that are helpful during the export file generation.

These methods are generally not intended to be overridden in the export implementation even though they are not declared as final.

#### public function getFileName(): string

This method returns the name to use for the export file within the Magento var/ directory.

If a file name is configured in the grid export configuration, that will be used. Otherwise the grid name with the export type code as a file name suffix will be used as a default.

#### public function getContentType(): string

This method is used by the download controller to determine the HTTP response content type header value. The content type defaults to  `application/octet-stream`, which triggers a file save dialog in browsers.

Usually the content type does not need to be changed for export type implementations.

#### public function getExportDir(): string

This method returns the code for the Magento directory in which the file name returned by `getFileName()` return value is created. It defaults to the Magento var/ directory, which should be fine for almost all cases.

(See `Magento\Framework\App\Filesystem\DirectoryList` for a list of all Magento directory codes.

#### protected function getGrid(): HyvaGridExportInterface

This method can be used to retrieve the instance of the grid that is being exported.

For example, this could potentially be useful to get the grid name `$this->getGrid()->getGridName()`.

#### protected function getHeaderData(): array

This method can  be used by export implementations to fetch the column names of the grid as an array.

#### protected function iterateGrid():Iterator

This method returns an iterator over all grid rows. The grid data will be loaded in batches of 200 rows at a time as to not exhaust memory.
