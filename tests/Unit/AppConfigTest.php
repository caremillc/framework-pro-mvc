<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    public function testAppConfiguration()
    {
        $config = require BASE_PATH . '/../config/app.php';
        
        $this->assertEquals('Careminate', $config['app_name']); // Changed from 'Caremi'
        // $this->assertTrue($config['debug']);
         $this->assertFalse($config['debug']);
        $this->assertArrayHasKey('Response', $config['aliases']);
    }
}
