<?php declare (strict_types = 1);
namespace Careminate\Tests\Unit;

use Careminate\Http\Responses\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResponseTraitsTest extends TestCase
{
     protected function setUp(): void
    {
        // Ensure macros are registered
        // require __DIR__.'/../../../bootstrap/macros.php';
    }

        public function testJsonResponseTrait()
    {
        $success = Response::success('Operation successful', ['id' => 123]);
        $error = Response::error('Validation failed', ['email' => 'Invalid']);

        $this->assertEquals(
            '{"success":true,"message":"Operation successful","data":{"id":123}}',
            $success->getContent()
        );

        $this->assertEquals(
            '{"success":false,"message":"Validation failed","errors":{"email":"Invalid"}}',
            $error->getContent()
        );
    }

    public function testFileDownloadTrait()
    {
        $testFile = sys_get_temp_dir() . '/testfile.txt';
        file_put_contents($testFile, 'Test content');

        $response = Response::download($testFile);

        // Don't check content as it's streamed directly
        $this->assertStringContainsString(
            'attachment; filename="testfile.txt"',
            $response->getHeader('Content-Disposition')
        );
        $this->assertEquals(
            (string) filesize($testFile),
            $response->getHeader('Content-Length')
        );

        unlink($testFile);
    }

    public function testThrowableHandlingTrait()
    {
        $exception = new RuntimeException('Test error', 500);
        $response  = Response::fromThrowable($exception);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Test error', $data['message']);
        $this->assertEquals(500, $response->getStatus());
        $this->assertArrayHasKey('trace', $data);
    }
}
