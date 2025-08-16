<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use Careminate\Http\Responses\Response;
use RuntimeException;

trait HandlesFileResponses
{
    public static function download(string $filePath, ?string $filename = null, array $headers = []): Response
    {
        $realPath = realpath($filePath);
        if ($realPath === false || str_contains($realPath, "\0")) {
            throw new RuntimeException("Invalid file path: {$filePath}");
        }

        $filename ??= basename($realPath);
        $filename = preg_replace('/[^\w\.\-]/', '_', $filename);

        $response = new Response('', 200, array_merge([
            'Content-Description' => 'File Transfer',
            'Content-Type' => mime_content_type($realPath) ?: 'application/octet-stream',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
            'Content-Length' => (string) filesize($realPath),
            'X-Content-Type-Options' => 'nosniff'
        ], $headers));

        $response->sendHeaders();

        $chunkSize = 8192;
        $handle = fopen($realPath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            ob_flush();
            flush();
        }
        fclose($handle);

        return $response;
    }

    public static function stream(callable $callback, int $status = 200, array $headers = []): Response
    {
        $response = new Response('', $status, array_merge([
            'Content-Type'      => 'application/octet-stream',
            'Transfer-Encoding' => 'chunked',
        ], $headers));

        $response->sendHeaders();
        $callback();

        return $response;
    }
}
