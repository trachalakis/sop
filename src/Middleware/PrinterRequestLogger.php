<?php

declare(strict_types=1);

namespace Middleware;

use Application\Services\PrinterRequestLog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class PrinterRequestLogger implements MiddlewareInterface
{
    public function __construct(private PrinterRequestLog $log)
    {
    }

    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $started = microtime(true);

        $requestBody = (string) $request->getBody();
        if ($request->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        $response = $handler->handle($request);

        $responseBody = (string) $response->getBody();
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        $this->log->record([
            'ts'            => $started,
            'method'        => $request->getMethod(),
            'path'          => $request->getUri()->getPath(),
            'status'        => $response->getStatusCode(),
            'duration_ms'   => round((microtime(true) - $started) * 1000, 1),
            'request_body'  => $requestBody,
            'response_body' => $responseBody,
        ]);

        return $response;
    }
}
