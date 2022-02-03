# Installation

## Requirements

* Magento 2.4.0 CE (the `$escaper` variable needs to be declared in templates)
* PHP >= 7.3
* Access to the Hyvä repository

## Getting Started

The module can be installed via composer by adding the repository as a source and then requiring it:

```
composer require hyva-themes/module-magento2-admin
```

If you want to just play around to get a feel for Hyva_Admin grids, you can install a test module that declares an example grid, too:

```
composer require hyva-themes/module-magento2-admin-test
```

Note: development of the module uses PHP 7.4, but the releases are based on a branch that is automatically backported, thanks to rector.

## Stability

The API will remain stable, unless some real flaw is discovered.

New features will be added in a backward compatible manner.
