<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\DataType\ProductGalleryDataType;
use Hyva\Admin\Model\DataType\ProductGalleryEntryDataType;
use Hyva\Admin\Model\DataType\ProductImageDataType;
use Hyva\Admin\Model\ProductMediaInBackend;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ProductMediaInBackendTest extends TestCase
{
    public static function imageFileProvider(): array
    {
        return [
            'plain'         => ['/a/b/image.jpg'],
            'ampersand'     => ['/a/b/image&size=1.jpg'],
            'double quotes' => ['/a/b/x" onerror="alert(1)" data-x=".jpg'],
            'single quotes' => ["/a/b/x' onerror='alert(1)' data-x='.jpg"],
            'closing tag'   => ['/a/b/x"><script>alert(1)</script>.jpg'],
            'html entity'   => ['/a/b/x&quot; onerror=&quot;alert(1).jpg'],
        ];
    }

    private function getProductMedia(): ProductMediaInBackend
    {
        return ObjectManager::getInstance()->get(ProductMediaInBackend::class);
    }

    private function assertSingleImageElement(string $html, string $expectedSrc, string $expectedAlt): void
    {
        $dom = new \DOMDocument();
        $dom->loadHTML(
            '<html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR
        );
        $body = $dom->getElementsByTagName('body')->item(0);

        $this->assertSame(1, $body->childNodes->length, 'Expected a single element, got: ' . $html);
        $img = $body->firstChild;
        $this->assertSame('img', $img->nodeName);

        $attributes = [];
        foreach ($img->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }
        $this->assertSame(['src', 'alt', 'loading'], $attributes, 'Unexpected attributes in: ' . $html);
        $this->assertSame($expectedSrc, $img->getAttribute('src'));
        $this->assertSame($expectedAlt, $img->getAttribute('alt'));
    }

    /**
     * @dataProvider imageFileProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('imageFileProvider')]
    public function testEscapesImageUrlInImageHtmlElement(string $file): void
    {
        $productMedia = $this->getProductMedia();

        $html = $productMedia->getImageHtmlElement($file, 'Label');

        $this->assertSingleImageElement($html, $productMedia->getImageUrl($file), 'Label');
    }

    public function testEscapesAltTextInImageHtmlElement(): void
    {
        $productMedia = $this->getProductMedia();
        $altText      = 'x" onerror="alert(1)" data-x="';

        $html = $productMedia->getImageHtmlElement('/a/b/image.jpg', $altText);

        $this->assertSingleImageElement($html, $productMedia->getImageUrl('/a/b/image.jpg'), $altText);
    }

    /**
     * @dataProvider imageFileProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('imageFileProvider')]
    public function testEscapesImageUrlInImageDataTypeHtml(string $file): void
    {
        $dataType = ObjectManager::getInstance()->get(ProductImageDataType::class);

        $html = $dataType->toHtmlRecursive($file);

        $this->assertSingleImageElement($html, $this->getProductMedia()->getImageUrl($file), 'Product Image');
    }

    /**
     * @dataProvider imageFileProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('imageFileProvider')]
    public function testEscapesImageUrlInGalleryDataTypeHtml(string $file): void
    {
        $dataType = ObjectManager::getInstance()->get(ProductGalleryDataType::class);

        $html = $dataType->toHtmlRecursive(['images' => [['file' => $file]]]);

        $this->assertSingleImageElement($html, $this->getProductMedia()->getImageUrl($file), 'Gallery Entry');
    }

    /**
     * @dataProvider imageFileProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('imageFileProvider')]
    public function testEscapesImageUrlInGalleryEntryDataTypeHtml(string $file): void
    {
        $objectManager = ObjectManager::getInstance();
        $dataType      = $objectManager->get(ProductGalleryEntryDataType::class);
        /** @var ProductAttributeMediaGalleryEntryInterface $entry */
        $entry = $objectManager->create(ProductAttributeMediaGalleryEntryInterface::class);
        $entry->setFile($file);
        $entry->setLabel('Entry "label"');

        $html = $dataType->toHtmlRecursive($entry);

        $this->assertSingleImageElement($html, $this->getProductMedia()->getImageUrl($file), 'Entry "label"');
    }
}
