<?php declare(strict_types=1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Contracts\ResponseInterface;
use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class ResponseInterfaceTest extends TestCase
{
    public function testInterfaceImplementation()
    {
        $response = new Response();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        
        // Test interface methods
        $response->setHeader('X-Test', 'Value');
        $this->assertEquals('Value', $response->getHeader('X-Test'));
        $this->assertIsInt($response->getStatus());
        $this->assertIsBool($response->areHeadersSent());
    }
}