<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * testBasicResponse
     *
     * @return void
     */
    public function testBasicResponse()
    {
        $response = new Response();
        $response->setContent('Hello');
        $response->setHeader('X-Test', 'Value');

        $this->assertEquals('Hello', $response->getContent());
        $this->assertEquals('Value', $response->getHeader('X-Test'));
    }

    /**
     * testSendMethod
     *
     * @return void
     */
    public function testSendMethod()
    {
        $response = new Response('Test Content', 202);
        $response->disableStreaming(); // <-- Disable chunked flushing

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertEquals('Test Content', $output);
        $this->assertEquals(202, $response->getStatus());
    }

    public function testSendContentDirectly()
    {
        $response = new Response('Direct Content', 200);
        $response->disableStreaming();

        ob_start();
        $reflection = new \ReflectionClass($response);
        $method     = $reflection->getMethod('sendContent');
        $method->setAccessible(true);
        $method->invoke($response);
        $output = ob_get_clean();

        $this->assertEquals('Direct Content', $output);
    }

    /**
     * testJsonResponse
     *
     * @return void
     */
    public function testJsonResponse()
    {
        $response = Response::json(['message' => 'Hello']);

        $this->assertEquals('{"message":"Hello"}', $response->getContent());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
    }

}
