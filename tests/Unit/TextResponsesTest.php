<?php declare(strict_types=1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class TextResponsesTest extends TestCase
{
    public function testTextResponses()
    {
        $responses = [
            Response::notFound(),
            Response::badRequest(),
            Response::unauthorized(),
            Response::forbidden(),
            Response::serverError()
        ];
        
        $statusCodes = [404, 400, 401, 403, 500];
        
        foreach ($responses as $i => $response) {
            $this->assertEquals(
                'text/plain; charset=UTF-8',
                $response->getHeader('Content-Type')
            );
            $this->assertEquals($statusCodes[$i], $response->getStatus());
        }
    }
}