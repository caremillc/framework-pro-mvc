<?php declare (strict_types = 1);

namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;

class StreamResponseTest extends TestCase
{
    public function testStreamResponse()
    {
        // Mock the output
        $output = '';
        $this->expectOutputString("Chunk1\nChunk2\n");

        $response = Response::stream(function () {
            echo "Chunk1\n";
            flush();
            echo "Chunk2\n";
            flush();
        });

        $this->assertEquals(
            'application/octet-stream',
            $response->getHeader('Content-Type')
        );
    }
}
