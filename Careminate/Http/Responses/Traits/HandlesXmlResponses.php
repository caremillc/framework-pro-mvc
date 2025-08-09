<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use SimpleXMLElement;
use Careminate\Http\Responses\Response;

trait HandlesXmlResponses
{
    public static function xml(array|string $data, int $status = 200, array $headers = [], string $root = 'response'): Response
    {
        $xmlContent = is_array($data) ? static::toXml($data, null, $root) : $data;

        return new Response($xmlContent, $status, array_merge([
            'Content-Type' => 'application/xml; charset=' . Response::DEFAULT_CHARSET,
        ], $headers));
    }

    protected static function toXml(array $data, ?SimpleXMLElement $xml = null, string $root = 'response'): string
    {
        $xml ??= new SimpleXMLElement("<?xml version=\"1.0\"?><$root/>");

        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? "item$key" : $key;
            if (is_array($value)) {
                static::toXml($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }

        return $xml->asXML();
    }
}
