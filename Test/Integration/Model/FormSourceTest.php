<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\FormSource;
use Hyva\Admin\Test\Integration\Model\FormSource\StubFormSourceTarget;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class FormSourceTest extends TestCase
{
    private function createFormSource(array $config): FormSource
    {
        $arguments = [
            'formName'   => 'test',
            'loadConfig' => $config['load'] ?? [],
            'saveConfig' => $config['save'] ?? [],
        ];
        return ObjectManager::getInstance()->create(FormSource::class, $arguments);
    }

    public function testLoadExplicitlySpecifiedType(): void
    {
        $config = [
            'load' => [
                'method' => CustomerRepositoryInterface::class . '::getById',
                'type'   => Product::class,
            ],
        ];
        $this->assertSame(Product::class, $this->createFormSource($config)->getLoadType());
    }

    public function testLoadMethodReturnType(): void
    {
        $config = [
            'load' => [
                'method' => CustomerRepositoryInterface::class . '::getById',
            ],
        ];
        $this->assertSame(CustomerInterface::class, ltrim($this->createFormSource($config)->getLoadType(), '\\'));
    }

    public function testLoadFallsBackToSaveMethodExplicitType(): void
    {
        $config = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::saveWithTypeParam',
                'type'   => Product::class,
            ],
        ];
        $this->assertSame(Product::class, ltrim($this->createFormSource($config)->getLoadType(), '\\'));
    }

    public function testLoadFallsBackToSaveMethodParameterType(): void
    {
        $config = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::saveWithTypeParam',
            ],
        ];
        $this->assertSame(ProductInterface::class, ltrim($this->createFormSource($config)->getLoadType(), '\\'));
    }

    public function testLoadFallsBackToSaveMethodReturnType(): void
    {
        $config = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::saveOnlyReturnType',
            ],
        ];
        $this->assertSame(ProductInterface::class, ltrim($this->createFormSource($config)->getLoadType(), '\\'));
    }

    public function testLoadDefaultsToArrayType(): void
    {
        $config = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
        ];
        $this->assertSame('array', ltrim($this->createFormSource($config)->getLoadType(), '\\'));
    }

    public function testSaveExplicitlySpecifiedType(): void
    {
        $config = [
            'save' => [
                'method' => CustomerRepositoryInterface::class . '::save',
                'type'   => Product::class,
            ],
        ];
        $this->assertSame(Product::class, $this->createFormSource($config)->getSaveType());
    }

    public function testSaveFallsBackToFormDataArgumentType(): void
    {
        $config = [
            'save' => [
                'method'        => StubFormSourceTarget::class . '::saveWithTypeParam',
                'bindArguments' => [
                    'arg2' => ['formData' => 'true'],
                ],
            ],
        ];
        $this->assertSame(BlockInterface::class, $this->createFormSource($config)->getSaveType());
    }

    public function testSaveFallsBackToFirstArgumentType(): void
    {
        $config = [
            'save' => [
                'method'        => StubFormSourceTarget::class . '::saveWithTypeParam',
                'bindArguments' => [
                ],
            ],
        ];
        $this->assertSame(ProductInterface::class, $this->createFormSource($config)->getSaveType());
    }

    public function testSaveFallsBackToSaveMethodReturnType(): void
    {
        $config = [
            'save' => [
                'method' => StubFormSourceTarget::class . '::saveOnlyReturnType',
            ],
        ];
        $this->assertSame(ProductInterface::class, $this->createFormSource($config)->getSaveType());
    }

    public function testSaveFallsBackToExplicitLoadType(): void
    {
        $config = [
            'load' => [
                'method' => CustomerRepositoryInterface::class . '::getById',
                'type'   => ProductInterface::class,
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
        ];
        $this->assertSame(ProductInterface::class, $this->createFormSource($config)->getSaveType());
    }

    public function testSaveFallsBackToLoadMethodReturnType(): void
    {
        $config = [
            'load' => [
                'method' => CustomerRepositoryInterface::class . '::getById',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
        ];
        $this->assertSame(CustomerInterface::class, ltrim($this->createFormSource($config)->getSaveType(), '\\'));
    }

    public function testSaveDefaultsToArrayType(): void
    {
        $config = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
        ];
        $this->assertSame('array', ltrim($this->createFormSource($config)->getSaveType(), '\\'));
    }

    public function testThrowsExceptionIfLoadMethodDoesNotExist(): void
    {
        $loadMethod = StubFormSourceTarget::class . '::thisDoesNotExist';
        $config     = [
            'load' => [
                'method' => $loadMethod,
            ],
            'save' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectDeprecationMessage(sprintf('Load method "%s" for form "test" not found', $loadMethod));

        $this->createFormSource($config)->getLoadMethodName();
    }

    public function testThrowsExceptionIfSaveMethodDoesNotExist(): void
    {
        $saveMethod = StubFormSourceTarget::class . '::thisDoesNotExist';
        $config     = [
            'load' => [
                'method' => StubFormSourceTarget::class . '::noType',
            ],
            'save' => [
                'method' => $saveMethod,
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectDeprecationMessage(sprintf('Save method "%s" for form "test" not found', $saveMethod));

        $this->createFormSource($config)->getSaveMethodName();
    }
}
