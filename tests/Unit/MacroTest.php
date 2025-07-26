<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class MacroTest extends TestCase
{
    public function testResponseMacro()
    {
        Response::macro('custom', function (string $message) {
            return $this->content("Custom: $message");
        });

        $response = new Response();
        $response = $response->custom('Hello');

        $this->assertEquals('Custom: Hello', $response->getContent());
    }

    public function testMacroRegistration()
    {
        $this->assertTrue(Response::hasMacro('maintenance'));
    }
}
