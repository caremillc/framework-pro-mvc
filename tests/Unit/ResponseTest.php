<?php declare (strict_types = 1);

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function test_SetContent()
    {
        $response = new Response('Test content');
        $this->assertSame('Test content', $response->getContent());
    }

    public function test_JsonResponse()
    {
        $response = (new Response())->json(['status' => 'ok']);
        $this->assertSame('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('"status":"ok"', $response->getContent());
    }

    public function testStatusCode()
    {
        $response = new Response('OK', Response::HTTP_OK);
        $this->assertTrue($response->isSuccessful());
    }

    public function testDownloadResponse()
    {
        $file = __DIR__ . '/test.txt';
        file_put_contents($file, 'downloadable content');

        $response = Response::download($file);
        $this->assertSame('attachment; filename="test.txt"', $response->getHeader('Content-Disposition'));
        $this->assertSame('downloadable content', $response->getContent());

        unlink($file);
    }

    public function testXmlResponse()
    {
        $response = Response::xml(['user' => ['name' => 'John']]);
        $this->assertSame('application/xml; charset=UTF-8', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('<name>John</name>', $response->getContent());
    }

    public function testFromThrowable()
    {
        $e        = new \RuntimeException('Whoops', 123);
        $response = Response::fromThrowable($e);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertStringContainsString('"error":true', $response->getContent());
    }

    public function testSuccessFactory()
    {
        $response = Response::success('All good', ['x' => 1]);
        $this->assertSame(Response::HTTP_OK, $response->getStatus());
        $this->assertStringContainsString('"success":true', $response->getContent());
    }

    public function testErrorFactory()
    {
        $response = Response::error('Oops!', ['field' => 'required']);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('"success":false', $response->getContent());
    }

}
