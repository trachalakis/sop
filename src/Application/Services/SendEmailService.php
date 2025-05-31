<?php

declare(strict_types=1);

namespace Application\Actions;

use Symfony\Component\Mailer\MailerInterface;
//use Symfony\Component\Mime\Email;

final class SendEmailService
{
	private $emailMessage;

	private $mailer;

	private $view;

	public function __construct(MailerInterface $mailer, $view)
	{
		$this->emailMessage = $emailMessage;
		$this->mailer = $mailer;
		$this->view = $view;
	}

	public function __invoke(array $emailMessageParams, string $emailTemplate = null, array $emailTemplateParams = null)
	{
        $this->emailMessage->setContentType('text/html')
        	->setFrom('app@villas4u.com');

        foreach($emailMessageParams as $k => $v) {
        	if ($k == 'from') {
        		continue;
        	}
        	$this->emailMessage->{'set' . ucfirst($k)}($v);
        }

        if ($emailTemplate != null && $emailTemplateParams != null) {
	        $template = $this->view->getEnvironment()->loadTemplate($emailTemplate);
	        $emailBody = $template->render($emailTemplateParams);

	        $this->emailMessage->setBody($emailBody);
	    }

        $this->mailer->send($this->emailMessage);
	}
}