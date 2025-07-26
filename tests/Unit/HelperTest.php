<?php declare(strict_types=1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use Careminate\Http\Responses\RedirectResponse;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_DEBUG=true');
        putenv('TEST_VALUE=123');
    }

    public function testEnvHelper()
    {
        $this->assertEquals('123', env('TEST_VALUE'));
        $this->assertEquals(true, env('APP_DEBUG'));
        $this->assertEquals('default', env('NON_EXISTENT', 'default'));
    }

    public function testResponseHelper()
    {
        $response = response('Helper Content', 203, ['X-Helper' => 'Test']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Helper Content', $response->getContent());
        $this->assertEquals(203, $response->getStatus());
    }
    
    public function testRedirectHelper()
    {
        $redirect = redirect('/profile', 308);
        
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertEquals('/profile', $redirect->getHeader('Location'));
        $this->assertEquals(308, $redirect->getStatus());
    }
}