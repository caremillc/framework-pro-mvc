<?php declare(strict_types=1);

namespace Careminate\Http\Responses\Traits;

use Throwable;
use Careminate\Http\Responses\Response;

trait HandlesThrowableResponses
{
    public static function fromThrowable(Throwable $e): Response
    {
        $isDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);

        $data = ['error' => true, 'message' => $e->getMessage()];

        if ($isDebug) {
            $data += [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
        }

        return Response::json($data, 500);
    }
}
