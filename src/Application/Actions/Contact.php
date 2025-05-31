<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

final class Contact
{
	private Twig $twig;

	private Mailer $mailer;

	public function __construct(Twig $twig, Mailer $mailer)
	{
		$this->twig = $twig;
		$this->mailer = $mailer;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();
			
			$email = new Email;
			$email->from('contact@almyriki.gr');
			$email->to('contact@almyriki.gr');
			$email->subject('Contact form | Almyriki.gr');
			$email->html(sprintf("
				Name: %s<br/>Email: %s<br>Message: %s",
				$requestData['fullName'],
				$requestData['emailAddress'],
				$requestData['message']
			));

			$this->mailer->send($email);

            $response->getBody()->write('ok');

            return $response;
        }
        
		return $this->twig->render(
			$response,
			'contact.twig',
			['language' => $args['language']]
		);
	}
}