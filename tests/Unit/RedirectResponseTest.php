<?php declare(strict_types=1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\RedirectResponse;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    public function testRedirectCreation()
    {
        $redirect = new RedirectResponse('/login', 301);
        
        $this->assertEquals(301, $redirect->getStatus());
        $this->assertEquals('/login', $redirect->getHeader('Location'));
    }

    public function testInvalidUrlValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RedirectResponse('invalid-url');
    }

    public function testSendWithoutExit()
    {
        $redirect = (new RedirectResponse('/dashboard'))
            ->setExitAfterRedirect(false);
        
        ob_start();
        $redirect->send();
        $output = ob_get_clean();
        
        $this->assertEquals('', $output);
        $this->assertEquals(302, http_response_code());
    }
}
