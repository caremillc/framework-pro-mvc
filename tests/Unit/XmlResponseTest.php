<?php declare(strict_types=1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class XmlResponseTest extends TestCase
{
    public function testXmlResponse()
    {
        $response = Response::xml(['user' => ['name' => 'John', 'age' => 30]]);
        $expected = '<?xml version="1.0"?><response><user><name>John</name><age>30</age></user></response>';
        
        $this->assertXmlStringEqualsXmlString($expected, $response->getContent());
        $this->assertEquals(
            'application/xml; charset=UTF-8',
            $response->getHeader('Content-Type')
        );
    }
}
