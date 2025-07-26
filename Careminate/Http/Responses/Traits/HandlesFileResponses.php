<?php declare (strict_types = 1);

namespace Careminate\Http\Responses\Traits;

use Careminate\Http\Responses\Response;
use RuntimeException;

trait HandlesFileResponses
{
    public static function download(string $filePath, ?string $filename = null, array $headers = []): Response
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        $filename ??= basename($filePath);

        $response = new Response('', 200, array_merge([
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => mime_content_type($filePath),
            'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => '0',
            'Cache-Control'             => 'must-revalidate',
            'Pragma'                    => 'public',
            'Content-Length'            => (string) filesize($filePath),
        ], $headers));

        $response->sendHeaders();
        readfile($filePath);

        return $response;
    }

    public static function stream(callable $callback, int $status = 200, array $headers = []): Response
    {
        $response = new Response('', $status, array_merge([
            'Content-Type'      => 'application/octet-stream',
            'Transfer-Encoding' => 'chunked',
        ], $headers));

        // Don't manipulate output buffers here - let the caller handle it
        $response->sendHeaders();
        $callback();

        return $response;
    }
}
