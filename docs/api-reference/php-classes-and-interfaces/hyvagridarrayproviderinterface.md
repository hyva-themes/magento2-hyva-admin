# HyvaGridArrayProviderInterface

The HyvaGridArrayProviderInterface is used for creating simple grid data providers.

## Overview

Often creating a repository is overkill when a simple list of things should be displayed in the admin.

This is where the array grid providers shine.

```php
<?php declare(strict_types=1);

namespace HyvaAdminApi;

/**
 * Implement this interface and specify that class as an array source type for a hyva grid.
 * Return an array with one sub-array for each row of the grid.
 */
interface HyvaGridArrayProviderInterface
{
    /**
     * @return array[]
     */
    public function getHyvaGridData(): array;
}
```

A array provider implementations are then configured as source of a grid in the grid XML:

```html
<source>
    <arrayProvider>Hyva\AdminTest\Model\LogFileListProvider</arrayProvider>
</source>
```

It is super simple to create array grid data providers.

There are some helpful methods to covert things into arrays in the Magento framework, for example `Magento\Framework\Reflection\DataObjectProcessor::buildOutputDataArray`. They can be useful sometimes, but of course anything can be used as long as the interface contract is fulfilled.

## Interface Methods

### getHyvaGridData(): array

The interface only has a single method. It returns an array of arrays.

Each sub-array, or record, is a row in the grid.

Here is a simplified example to clarify the return array structure:

```php
public function getHyvaGridData(): array
{
    return [
        ['col-A' => 'the first value', 'col-B' => 'another value'],
        ['col-A' => 'more data',       'col-B' => 'even more data'],
        ...
    ];
}
```

The array keys of the first record are used to determine the columns for the grid.

Example grid array provider that lists all files in the Magento `var/log/` directory:

```php
<?php declare(strict_types=1);

namespace HyvaAdminTestModel;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\FileFactory;

class LogFileListProvider implements HyvaGridArrayProviderInterface
{
    private DirectoryList $directoryList;

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

