<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Application\Services\PrinterRequestLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PrinterRequestsData
{
    public function __construct(private PrinterRequestLog $log)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $entries = array_reverse($this->log->getRecent());

        $response->getBody()->write(json_encode($entries));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
