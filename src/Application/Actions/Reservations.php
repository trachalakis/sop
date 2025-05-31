<?php

declare(strict_types=1);

namespace Application\Actions;

use Domain\Entities\Reservation;
use Domain\Repositories\ReservationsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

final class Reservations
{
	private Twig $twig;

	private ReservationsRepositoryInterface $reservationsRepository;

	private Mailer $mailer;

	public function __construct(Twig $twig, ReservationsRepositoryInterface $reservationsRepository, Mailer $mailer)
	{
		$this->twig = $twig;
		$this->reservationsRepository = $reservationsRepository;
		$this->mailer = $mailer;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		if ($request->getMethod() == 'POST') {
            $reservationData = $request->getParsedBody();

            $reservation = new Reservation;
            $reservation->setStatus('UNCONFIRMED');
            $reservation->setCreatedAt(new \Datetime);
            $reservation->setDateTime(new \Datetime($reservationData['date'] . ' ' . $reservationData['time']));
            $reservation->setName($reservationData['name']);
            $reservation->setAdults(intval($reservationData['adults']));
            $reservation->setMinors(intval($reservationData['minors']));
            $reservation->setTelephoneNumber($reservationData['telephoneNumber']);
            $reservation->setEmailAddress($reservationData['emailAddress']);
            $reservation->setMessage($reservationData['message']);

            $this->reservationsRepository->persist($reservation);

            $email = new Email;
			$email->from('contact@almyriki.gr');
			$email->to($reservation->getEmailAddress());
			$email->subject('Almyriki Restaurant');
			$email->html("We have received your reservation request and we will try to confirm it as soon as possible.");
			$this->mailer->send($email);

			$email = new Email;
			$email->from('appt@almyriki.gr');
			$email->to('contact@almyriki.gr');
			$email->subject('Reservation request');
			$email->html(sprintf(
				"New reservation request. <a href=\"https://almyriki.gr/admin/reservations/update?id=%s\">Click here</a>",
				$reservation->getId()
			));
			$this->mailer->send($email);

            $response->getBody()->write('ok');
            return $response;
        }

		return $this->twig->render(
			$response,
			'reservations.twig',
			['language' => $args['language']]
		);
	}
}