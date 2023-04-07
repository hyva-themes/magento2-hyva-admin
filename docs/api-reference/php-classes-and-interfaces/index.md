# PHP Classes and Interfaces

The nested documents describe the PHP classes and interfaces you might interact with in some way while using Hyva_Admin.

The interfaces intended for implementation can be found in the Hyva_Admin module directory `Api/`.

Chances are you will never need to do this. But if you do, well, you can.

Currently these are (in alphabetical order, together with the probability you might want to implement them):

* `Hyva\Admin\Api\DataTypeGuesserInterface` (very unlikely)
* `Hyva\Admin\Api\DataTypeInterface` (very unlikely)
* `Hyva\Admin\Api\DataType\ValueToStringConverterInterface` (maybe)
* `Hyva\Admin\Api\HyvaGridArrayProviderInterface` (likely)
* `Hyva\Admin\Api\HyvaGridFilterTypeInterface` (unlikely)
* `Hyva\Admin\Api\HyvaGridSourceProcessorInterface` (unlikely)
* `Hyva\Admin\Model\Grid\ExportType\AbstractExportType` (maybe)
* `Hyva\Admin\Model\Grid\Source\AbstractGridSourceProcessor` (likely)

Each of the above interfaces is document in this section of the API reference.

There also are a number of classes and interfaces that are not intended to be implemented, but only to be used, mostly when creating custom cell and filter templates:

These mostly are (in alphabetical order):

* `Hyva\Admin\Block\Adminhtml\HyvaGrid` (only when declaring the grid block in layout XML)
* `Hyva\Admin\ViewModel\HyvaGrid\CellInterface` (in cell templates)
* `Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface` (in cell and filter templates)
* `Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface` (in filter templates)

These classes are not documented, however, the only point of contact will be inside of template files.

Adding a PHPDoc type hint to the template will allow IDE auto-completion to assist you. Hopefully you will find the method names to be nice and descriptive, and you will always be able to refer to the templates supplied as part of Hyva_Admin as a reference.
