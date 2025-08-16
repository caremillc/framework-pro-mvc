<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use Careminate\Http\Responses\Response;

trait HandlesJsonResponses
{
    public static function json(mixed $data, int $status = 200, array $headers = [], ?int $options = null, int $depth = 512): Response
    {
        $options ??= Response::getJsonOptions();

        try {
            $serializer = Response::getJsonSerializer();
            $content    = $serializer
                ? $serializer($data)
                : json_encode($data, $options, $depth);
        } catch (\JsonException $e) {
            throw new \JsonException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        return new Response($content, $status, array_merge([
            'Content-Type' => 'application/json; charset=' . Response::DEFAULT_CHARSET,
        ], $headers));
    }

    public static function streamJson(iterable $data): Response
    {
        $response = new Response('', 200, [
            'Content-Type'      => 'application/json; charset=' . Response::DEFAULT_CHARSET,
            'Transfer-Encoding' => 'chunked',
        ]);

        $response->sendHeaders();

        echo '[';
        $first = true;
        foreach ($data as $item) {
            if (!$first) {
                echo ',';
            }
            echo json_encode($item, Response::getJsonOptions());
            $first = false;
            ob_flush();
            flush();
        }
        echo ']';

        return $response;
    }
}
