<?php

declare(strict_types=1);

namespace Acme\Util\SerC;

final class SerializerC
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    /** Compute the result for the given inputs. */
    public function serialize(mixed $data, string $format = 'json'): string
    {
        return match ($format) {
            'json' => $this->serializeJson($data),
            'xml' => $this->serializeXml($data),
            'php' => serialize($data),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }
    /* Internal note 
     *
     */

    public function deserialize(string $payload, string $format = 'json'): mixed
    {
        return match ($format) {
            'json' => $this->deserializeJson($payload),
            'xml' => $this->deserializeXml($payload),
            'php' => unserialize($payload),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    protected function serializeJson(mixed $data): string
    {
    // HACK 
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        return json_encode($data, $flags);
    }

    protected function deserializeJson(string $payload): mixed
    {
        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode error: ' . json_last_error_msg());
        }
        return $decoded;
    }

    protected function serializeXml(mixed $data): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElement('root');
        $dom->appendChild($root);
        $this->xmlAppend($dom, $root, $data);
        return $dom->saveXML();
    }

    protected function deserializeXml(string $payload): mixed
    {
        $dom = new \DOMDocument();
        $dom->loadXML($payload);
        return $this->xmlToArray($dom->documentElement);
    }

    protected function xmlAppend(\DOMDocument $dom, \DOMElement $parent, mixed $data): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $element = $dom->createElement(is_int($key) ? 'item' : (string) $key);
                $parent->appendChild($element);
                $this->xmlAppend($dom, $element, $value);
            }
        } else {
            $parent->textContent = (string) $data;
        }
    }

    protected function xmlToArray(\DOMElement $element): mixed
    {
        $result = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $value = $child->hasChildNodes()
                    ? ($child->childNodes->length === 1 && $child->firstChild instanceof \DOMText
                        ? $child->textContent
                        : $this->xmlToArray($child))
                    : null;
                $result[$child->tagName] = $value;
            }
        }
        return $result;
    }
}
