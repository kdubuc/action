<?php

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function Kdubuc\Invocator\invoke;
use Psr\Http\Server\RequestHandlerInterface;

final class InvokeTest extends TestCase
{
    public function testInvoke() : void
    {
        // Body
        $body = json_encode(['foo' => 'bar', 'baz' => 'qux', 'test' => 2], JSON_THROW_ON_ERROR);

        // Build raw HTTP Request
        $request = "GET /abc/xyz?foo=bar HTTP/1.1\r\n";
        $request .= "Host: foo.com\r\n";
        $request .= "Foo: Bar\r\n";
        $request .= "Baz: Qux\r\n";
        $request .= "Cookie: yummy_cookie=choco; tasty_cookie=strawberry\r\n";
        $request .= "\r\n";
        $request .= $body;

        // Mock Request Handler
        $action = $this->createMock(RequestHandlerInterface::class);

        // Test the server request generated
        $action->method('handle')
            ->with(
                $this->callback(function ($server_request) {
                    return 'GET' == $server_request->getMethod() &&
                        $server_request->getQueryParams() == ['foo' => 'bar'] &&
                        $server_request->getCookieParams() == ['yummy_cookie' => 'choco', 'tasty_cookie' => 'strawberry'] &&
                        $server_request->getParsedBody() == ['foo' => 'bar', 'baz' => 'qux', 'test' => 2];
                })
            )
            ->willReturn(new Response());

        // Set test input
        $input = fopen('php://temp', 'r+');
        fwrite($input, $request);
        rewind($input);

        // Set test output
        $output = fopen('php://temp', 'r+');

        // Invoke action
        invoke($action, $input, $output);

        // Prepare output to be read and get the response result
        rewind($output);
        $response = stream_get_contents($output);
        $this->assertSame("HTTP/1.1 200 OK\r\n\r\n", $response);
    }
}
