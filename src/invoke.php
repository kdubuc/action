<?php

namespace Kdubuc\Invocator;

use function fwrite;
use function json_decode;
use function GuzzleHttp\Psr7\str;
use GuzzleHttp\Psr7\ServerRequest;
use function GuzzleHttp\Psr7\parse_query;
use function GuzzleHttp\Psr7\parse_header;
use function GuzzleHttp\Psr7\parse_request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pass HTTP request message into input resource (default: stdin) to invoke a PSR-15 Action and returns
 * raw HTTP response into output resource (default: stdout).
 */
function invoke(RequestHandlerInterface $action, $input = STDIN, $output = STDOUT) : void
{
    // Get raw HTTP message from input resource
    $http_message = stream_get_contents($input);

    // Parse raw HTTP string into a Request object
    $request = parse_request($http_message);

    // Transform Request into ServerRequest to be compliant with PSR-15.
    // As we don't rely on a SAPI, we build the server request with empty server params.
    // See also : https://www.php-fig.org/psr/psr-15/meta/#why-is-a-server-request-required-1
    $server_request =  new ServerRequest(
        $request->getMethod(),
        $request->getUri(),
        $request->getHeaders(),
        $request->getBody(),
        $request->getProtocolVersion()
    );

    // Parse URI query params and inject them into the server request
    $server_request = $server_request->withQueryParams(parse_query($request->getUri()->getQuery()));

    // Parse cookies headers and inject them into the server request
    if ($server_request->hasHeader('Cookie')) {
        $cookies = [];
        foreach (parse_header($request->getHeader('Cookie')) as $request_cookies) {
            $cookies = $cookies + $request_cookies;
        }
        $server_request = $server_request->withCookieParams($cookies);
    }

    // Decode JSON body (Invoke supports only JSON body)
    $server_request = $server_request->withParsedBody(json_decode($request->getBody(), true));

    // Invoke action, and get the response
    $response = $action->handle($server_request);

    // Write raw HTTP response in output resource
    fwrite($output, str($response));
}
