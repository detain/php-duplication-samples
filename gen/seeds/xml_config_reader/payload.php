<?php

declare(strict_types=1);

namespace Acme\Seed\XmlConfigReader;

/**
 * Seed payload: parse simple XML configuration into associative array.
 */
final class XmlConfigReaderSeed
{
    // <<<PAYLOAD:xml_config_reader>>>
    public function parseXmlConfig(string $xml): ?array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false) {
            return null;
        }
        return $this->xmlToArray($doc);
    }

    private function xmlToArray(\SimpleXMLElement $element): array
    {
        $result = [];
        if ($element->count() === 0) {
            return trim((string) $element);
        }
        foreach ($element->children() as $child) {
            $name = $child->getName();
            if (isset($result[$name])) {
                if (!is_array($result[$name]) || isset($result[$name][0])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $this->xmlToArray($child);
            } else {
                $result[$name] = $this->xmlToArray($child);
            }
        }
        foreach ($element->attributes() as $attr => $value) {
            $result['@' . $attr] = (string) $value;
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
