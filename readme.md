# Hyvä Admin

This module aims to make creating grids and forms in the Magento 2 adminhtml area joyful and fast.    
It does not use any UI components.

> They  came  round  the  corner,  and there was Eeyore's
  house, looking as comfy as anything.  
          "There you are," said Piglet.  
          "Inside as well as outside," said Pooh proudly.  
          Eeyore went inside . . . and came out again.  
          "It's a remarkable thing," he said. "It  is  my  house,
  and  I built it where I said I did, so the wind must have blown
  it here. And the wind blew it right over the wood, and blew  it
  down  here,  and here it is as good as ever. In fact, better in
  places."  
          "Much better," said Pooh and Piglet together.  
          "It just shows what can be  done  by  taking  a  little
  trouble,"  said Eeyore. "Do you see, Pooh? Do you see, Piglet?
  Brains first and then Hard Work. Look at it! That's the way  to
  build a house," said Eeyore proudly.

- Alan Alexander Miln, "The house at Pooh Corner"

## Overview

Hyva_Admin is a Magento 2 module that offers a new way to create admin grids.  
All existing grids and forms are not affected. They remain unchanged.  
Hyva_Admin exists to improve the developer experience while creating new grids.

In future, Hyva_Admin will support creating forms, too.

## Rationale

When using the Magento 2 UI Components to create admin grids and forms, I always felt like I was dying a bit inside. From my point of view it's an awful system for a number of reasons that I don't want to go into more details about here.
Alternative store fronts that do not use UI components (PWA Studio, Hyva Themes) are great for frontend developers, but (un?)fortunately I mostly do backend work. The UI interfaces I create are mostly for store owners and admins.
 
I desire a way to do my job (which includes building grids and forms) that doesn't feel like I have to fight the framework.  
I want to feel empowered and get work done quickly and efficiently.
After years of bitching about Magento, I was very impressed by the work Willem Wigman has done with the Hyvä frontend theme.
He inspired me to stop complaining and also take matters into my own hands, and finaly build the tools I desire.
Hence, Hyva_Admin. 


## Installation

The module can be installed via composer by adding the repository as a source and then requiring it: 

```
composer config repositories.hyva/module-magento2-admin git git@gitlab.hyva.io:hyva-admin/magento2-hyva-admin.git
composer require hyva/module-magento2-admin --prefer-source
```

If you want to just play arond to get a feel for Hyva_Admin grids, you can install a test module that declares an example grid, too:

```
composer config repositories.hyva/module-magento2-admin-test git git@gitlab.hyva.io:hyva-admin/hyva-admin-test-module.git
composer require hyva/module-magento2-admin-test --prefer-source
```

### Requirements

The module currently requires PHP 7.4, but I intend to refactor it so it works with PHP 7.3, too.
It should work with pretty much any Magento 2 version, as long as the `$escaper` is assigned in templates.


## Quickstart

Once installed, grids can be added to any admin page by adding a bit of layout XML.  
The layout XML has to contain two things:

* A `<update handle="hyva_admin_grid"/>` declaration to load alpine.js and tailwind.
* A `Hyva\Admin\Block\Adminhtml\HyvaGrid` block, with name of the grid configuration as a block argument.

After that, a grid configuration has to be created in a directory `[Your_Module]/view/adminhtml/hyva-grid`, where the
file names corresponds to the name that was passed to the grid block (with a `.xml` suffix added to the file name).

The grid configuration will need contain a grid source specification. Currently that can be a repository list method, or a
`\Hyva\Admin\Api\HyvaGridArrayProviderInterface` implementation.

With no further configuration, all fields of the provided records are shown as grid columns.
It's then possible to either exclude columns as needed, or, alternatively, specify an include-list for the columns to display.
In many cases the default will be good enough and no further configuration beyond the grid source will be necessary.

Grid row actions, mass actions, paging and filtering can also be configured as needed.

More information can be found in the [Hyva Admin docs](https://docs.hyva.io/doc/1-getting-started-VCwsPVVwTP).


## Examples 

In the following you will find a few examples that might be enough to get you started.
For more details and more structure can be found in the [docs](https://docs.hyva.io/doc/1-getting-started-VCwsPVVwTP).

### Example Layout XML

The following is all the layout XML that is required to show a Hyva admin grid on an admin page.  
The grid configuration would then be read from `view/adminhtml/hyva-grid/some-grid.xml`. 

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="hyva_admin_grid"/>
    <body>
        <referenceContainer name="content">
            <block class="Hyva\Admin\Block\Adminhtml\HyvaGrid" name="demo-grid">
                <arguments>
                    <argument name="grid_name" xsi:type="string">some-grid</argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```

#### Example Minimal Grid Configuration:

This is how a grid with an array provider source can be configured.
Technically not even an exclude column is required - but leaving only the source config in the example seemed like too little.

```xml
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <arrayProvider>\Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
    </source>
    <columns>
        <exclude>
            <column name="leaf"/>
        </exclude>
    </columns>
</grid>
```

#### Example Product Grid Configuration:

This is an example for a grid configuration that uses the product repository as a data source.  
It showcases more of the grid configuration options, but there are more.

```xml
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <repositoryListMethod>\Magento\Catalog\Api\ProductRepositoryInterface::getList</repositoryListMethod>
    </source>
    <columns>
        <include>
            <column name="id"/>
            <column name="sku"/>
            <column name="activity"/>
            <column name="name"/>
            <column name="image" type="magento_product_image" renderAsUnsecureHtml="true" label="Main Image"
                    template="Hyva_AdminTest::image.phtml"/>
            <column name="media_gallery" renderAsUnsecureHtml="true"/>
            <column name="price" type="price"/>
        </include>
        <exclude>
            <column name="category_gear"/>
        </exclude>
    </columns>
    <actions idColumn="id">
        <action id="edit" label="Edit" url="*/*/edit"/>
        <action id="delete" label="Delete" url="*/*/delete"/>
    </actions>
    <massActions idColumn="id">
        <action id="reindex" label="Reindex" url="*/massAction/reindex"/>
        <action id="delete" label="Delete" url="*/massAction/delete" requireConfirmation="true"/>
    </massActions>
    <navigation>
        <pager>
            <defaultPageSize>5</defaultPageSize>
            <pageSizes>2,5,10</pageSizes>
        </pager>
        <sorting>
            <defaultSortByColumn>sku</defaultSortByColumn>
            <defaultSortDirection>desc</defaultSortDirection>
        </sorting>
        <filters>
            <filter column="sku"/>
            <filter column="activity"/>
            <filter column="category_ids"/>
            <filter column="id"/>
            <filter column="color">
                <option label="reddish">
                    <value>16</value>
                    <value>17</value>
                    <value>18</value>
                </option>
                <option label="blueish">
                    <value>12</value>
                </option>
                <option label="rose">
                    <value>100</value>
                </option>
            </filter>
        </filters>
    </navigation>
</grid>
```

#### Example Array Grid Data Provider

This is an example for simple array grid data provider.
Each sub-array is a row in the grid. The grid columns are taken from the first record in the returned array. 

```php
<?php declare(strict_types=1);

namespace Hyva\AdminTest\Model;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\FileFactory;

class LogFileListProvider implements HyvaGridArrayProviderInterface
{

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var FileFactory
     */
    private FileFactory $fileFactory;

    public function __construct(DirectoryList $directoryList, FileFactory $fileFactory)
    {
        $this->directoryList = $directoryList;
        $this->fileFactory = $fileFactory;
    }

    public function getHyvaGridData(): array
    {
        $file = $this->fileFactory->create();
        $file->cd($this->directoryList->getPath(DirectoryList::LOG));

        return $file->ls();
    }
}
```

## Copyright & License

Copyright 2020 Vinai Kopp & Hyvä Themes BV

The module is released under the [BSD-3 Clause license](LICENSE.txt).

## Parting words

> "And  I  know  it  seems easy," said Piglet to himself,  
  "but it isn't every one who could do it."  
- Alan Alexander Miln, "The house at Pooh Corner"
