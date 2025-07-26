<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use JsonException;
use Careminate\Http\Responses\Response;

trait HandlesJsonResponses
{
    public static function json(
        mixed $data,
        int $status = 200,
        array $headers = [],
        ?int $options = null,
        int $depth = 512
    ): Response {
        $options ??= Response::getJsonOptions();

        try {
            $content = json_encode($data, $options, $depth);
        } catch (JsonException $e) {
            throw new JsonException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        return new Response($content, $status, array_merge([
            'Content-Type' => 'application/json; charset=' . Response::DEFAULT_CHARSET
        ], $headers));
    }

    public static function success(string $message = 'Success', array $data = [], int $status = 200): Response
    {
        return static::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message = 'An error occurred', array $errors = [], int $status = 400): Response
    {
        return static::json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
