# Array Grid Data Provider

This is an example for simple array grid data provider.

The class needs to implement `Hyva\Admin\Api\HyvaGridArrayProviderInterface`

The `getHyvaGridData` method returns an array with all the records.

Each record is a sub-array and will be rendered as a row in the grid.

The grid columns are taken from the array keys of the first record in the returned array.

```php
<?php declare(strict_types=1);

namespace Hyva\AdminTest\Model;

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

