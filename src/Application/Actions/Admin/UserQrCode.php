<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

final class UserQrCode
{
    private $twig;

    private $usersRepository;

    public function __construct(Twig $twig, UsersRepositoryInterface $usersRepository)
    {
    	$this->usersRepository = $usersRepository;
    	$this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	$user = $this->usersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$size = (mb_strlen($user->getFullName()) > 20) ? 18 : 20;

		$qrCode = Builder::create()
		    ->writer(new PngWriter())
		    ->writerOptions([])
		    ->data($user->getPasswordHash())
		    ->encoding(new Encoding('UTF-8'))
		    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
		    ->size(300)
		    ->margin(10)
		    ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
		    ->labelText($user->getFullName())
		    ->labelFont(new OpenSans($size))
		    ->labelAlignment(new LabelAlignmentCenter())
		    ->build();

		return $this->twig->render(
			$response,'admin/user_qr_code.twig',
			[
				'user' => $user,
				'qrCode' => $qrCode
			]
		);
    }
}