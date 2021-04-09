<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use function array_filter as filter;
use function array_values as values;

class XmlToArray
{
    public static function getRootElement(\DOMDocument $document): \DOMElement
    {
        return self::getAllChildElements($document)[0];
    }

    /**
     * @param \DOMNode $parent
     * @return \DOMElement[]
     */
    public static function getAllChildElements(\DOMNode $parent): array
    {
        return values(filter(
            iterator_to_array($parent->childNodes),
            function (\DOMNode $childNode) {
                return $childNode->nodeType === \XML_ELEMENT_NODE;
            }
        ));
    }

    /**
     * @param \DOMElement $parent
     * @param string $name
     * @return \DOMElement[]
     */
    public static function getChildrenByName(\DOMElement $parent, string $name): array
    {
        return values(filter(XmlToArray::getAllChildElements($parent), function (\DOMElement $child) use ($name) {
            return $child->nodeName === $name;
        }));
    }

    /**
     * @param \DOMElement $element
     * @param string $name
     * @return string[]
     */
    public static function getAttributeConfig(\DOMElement $element, string $name, string $withIndexKey = null): array
    {
        $idx = $withIndexKey ?? $name;
        $value = $element->getAttribute($name);
        return $value !== ''
            ? [$idx => $value]
            : [];
    }

    public static function getChildByName(\DOMElement $parent, string $name): ?\DOMElement
    {
        return self::getChildrenByName($parent, $name)[0] ?? null;
    }

    /**
     * @param \DOMElement $parent
     * @param string $name
     * @return string[]
     */
    public static function getElementConfig(\DOMElement $parent, string $name): array
    {
        $childElement = self::getChildByName($parent, $name);
        return $childElement
            ? filter([$name => trim($childElement->nodeValue)])
            : [];
    }
}
