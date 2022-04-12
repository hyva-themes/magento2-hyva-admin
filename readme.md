# Hyvä Admin

This module aims to make creating grids and forms in the Magento 2 adminhtml area joyful and fast.    
It does not use any UI components. Status: ![main branch tests](https://github.com/hyva-themes/magento2-hyva-admin/workflows/Hyv%C3%A4%20Admin%20Tests/badge.svg)

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

In the future, Hyva_Admin will support creating forms, too (this [issue](https://github.com/hyva-themes/magento2-hyva-admin/issues/36) tracks progress).


## Relationship with frontend Hyvä-Themes

This module does not require using a Hyva-Theme for a store front. It is an independent module.
It only shares three things with the Hyvä frontend theme:
- the idea that development should be enjoyable
- the framework should support developers instead of creating more work than necessary
- it uses tailwind css and alpinejs under the hood, but you will probably never even notice when using Hyva_Admin

You can install this module and enjoy it without having a Hyvä-Themes license.
That said, I really recommend you get a store front Hyvä license, and make work there enjoyable and fast, too.


## Rationale

When using the Magento 2 UI Components to create admin grids and forms, I always felt like I was dying a bit inside. From my point of view it's an awful system for a number of reasons that I don't want to go into more details about here.
Alternative store fronts that do not use UI components (PWA Studio, Hyva Themes) are great for frontend developers, but (un?)fortunately I mostly do backend work. The UI interfaces I create are mostly for store owners and admins.
 
I desire a way to do my job (which includes building grids and forms) that doesn't feel like I have to fight the framework.  
I want to feel empowered and get work done quickly and efficiently.
After years of bitching about Magento, I was very impressed by the work Willem Wigman has done with the Hyvä frontend theme.
He inspired me to stop complaining and also take matters into my own hands, and finally build the tools I desire.
Hence, Hyva_Admin. 


## Installation

The module can be installed via composer by adding the repository as a source and then requiring it: 

```
composer require hyva-themes/module-magento2-admin
```

If you want to just play around to get a feel for Hyva_Admin grids, you can install a test module that declares an example grid, too:

```
composer require hyva-themes/module-magento2-admin-test
```

### Requirements

It should work with pretty much any Magento 2 version, as long as the `$escaper` is assigned in templates.


## Quickstart

**Note:** Both an overview with a step by step walkthrough and an API reference can be found in the [docs](https://hyva-themes.github.io/magento2-hyva-admin/) folder.

Once installed, grids can be added to any admin page by adding a bit of layout XML and a grid configuration file.  
The layout XML has to contain two things:

* A `<update handle="hyva_admin_grid"/>` declaration to load alpine.js and tailwind.
* A `Hyva\Admin\Block\Adminhtml\HyvaGrid` block, with name of the grid configuration as a block argument (or as the block name-in-layout).

After that, a grid configuration has to be created in a directory `[Your_Module]/view/adminhtml/hyva-grid`, where the
file names corresponds to the name that was passed to the grid block (with a `.xml` suffix added to the file name).

The grid configuration will need contain a grid source specification. Currently that can be a repository list method, or a
`\Hyva\Admin\Api\HyvaGridArrayProviderInterface` implementation.

With no further configuration, all fields of the provided records are shown as grid columns.
It's then possible to either exclude columns as needed, or, alternatively, specify an include-list for the columns to display.
In many cases the default will be good enough and no further configuration beyond the grid source will be necessary.

Grid row actions, mass actions, paging and filtering can also be configured as needed.

More information can be found in the [Hyva Admin documentation](https://hyva-themes.github.io/magento2-hyva-admin/).


## Stability

The module isn’t feature complete.
However, the API will remain stable, unless some real flaw is discovered.  
New features will be added in a backward compatible manner.  

## Contributions

PR's are very welcome.  
Please submit contributions based on the main branch. Currently, the oldest supported PHP version is 7.4.

## Copyright & License

Copyright 2021 Vinai Kopp & Hyvä Themes BV

The module is released under the [BSD-3 Clause license](LICENSE.txt).

## Parting words

> "And  I  know  it  seems easy," said Piglet to himself,  
  "but it isn't every one who could do it."  
- Alan Alexander Miln, "The house at Pooh Corner"
