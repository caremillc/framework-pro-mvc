<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testBasicResponse()
    {
        $response = new Response();
        $response->setContent('Hello');
        $response->setHeader('X-Test', 'Value');

        $this->assertEquals('Hello', $response->getContent());
        $this->assertEquals('Value', $response->getHeader('X-Test'));
    }

    public function testSendMethod()
    {
        $response = new Response('Test Content', 202);
        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertEquals('Test Content', $output);
        $this->assertEquals(202, http_response_code());
    }

    public function testJsonResponse()
    {
        $response = Response::json(['message' => 'Hello']);

        $this->assertEquals('{"message":"Hello"}', $response->getContent());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function testMacroFunctionality()
    {
        Response::macro('uppercase', function () {
            return $this->content(strtoupper($this->getContent()));
        });

        $response = new Response('hello');
        $response = $response->uppercase();

        $this->assertEquals('HELLO', $response->getContent());
    }
}
