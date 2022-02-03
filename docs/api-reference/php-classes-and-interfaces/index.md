# PHP Classes and Interfaces

The nested documents describe the PHP classes and interfaces you might interact with in some way while using Hyva_Admin.

The interfaces intended for implementation can be found in the Hyva_Admin module directory `Api/`.

Chances are you will never need to do this. But if you do, well, you can.

Currently these are (in alphabetical order, together with the probability you might want to implement them):

* `HyvaAdminApiDataTypeGuesserInterface` (very unlikely)
* `HyvaAdminApiDataTypeInterface` (very unlikely)
* `HyvaAdminApiDataTypeValueToStringConverterInterface` (maybe)
* `HyvaAdminApiHyvaGridArrayProviderInterface` (likely)
* `HyvaAdminApiHyvaGridFilterTypeInterface` (unlikely)
* `HyvaAdminApiHyvaGridSourceProcessorInterface` (unlikely)
* `HyvaAdminModelGridExportTypeAbstractExportType` (maybe)
* `HyvaAdminModelGridSourceAbstractGridSourceProcessor` (likely)

Each of the above interfaces is document in this section of the API reference.

There also are a number of classes and interfaces that are not intended to be implemented, but only to be used, mostly when creating custom cell and filter templates:

These mostly are (in alphabetical order):

* `HyvaAdminBlockAdminhtmlHyvaGrid` (only when declaring the grid block in layout XML)
* `HyvaAdminViewModelHyvaGridCellInterface` (in cell templates)
* `HyvaAdminViewModelHyvaGridColumnDefinitionInterface` (in cell and filter templates)
* `HyvaAdminViewModelHyvaGridGridFilterInterface` (in filter templates)

These classes are not documented, however, the only point of contact will be inside of template files.

Adding a PHPDoc type hint to the template will allow IDE auto-completion to assist you. Hopefully you will find the method names to be nice and descriptive, and you will always be able to refer to the templates supplied as part of Hyva_Admin as a reference.
