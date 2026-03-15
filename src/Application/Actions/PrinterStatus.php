<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;


final class PrinterStatus
{
	private Logger $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		//$language = isset($args['language']) ? $args['language'] : 'en';
		
        /*$this->logger->debug(
            'Status: ' . $request->getBody()
        );*/
           
		//$response->getBody()->write('ok');
		return $response;
	}
}