<?php

declare(strict_types=1);

namespace Middleware;

use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Server\MiddlewareInterface;

final class Authorization implements MiddlewareInterface
{
    private UserPermissionsRepository $userPermissionsRepository;

    public function __construct(UserPermissionsRepository $userPermissionsRepository)
    {
    	$this->userPermissionsRepository = $userPermissionsRepository;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $user = $_SESSION['user'] ?? null;

        if ($user == null) {
        	$response = $handler->handle($request);
        	return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userRoleNames = array_map(fn($r) => $r->getName(), $user->getRoles());

        if (in_array('webmaster', $userRoleNames)) {
            $response = $handler->handle($request);
            return $response;
        }

        $permissions = $this->userPermissionsRepository->findAll();
        foreach ($permissions as $permission) {
            $path = str_replace('/', '\/', $permission->getPath());
            $path = sprintf("/%s/", $path);
            if (preg_match($path, $request->getUri()->getPath())) {
                if (count(array_intersect($permission->getAllowedRoles(), $userRoleNames)) > 0) {
                    $response = $handler->handle($request);
            		return $response;
                }
            }
        }

        $response = $handler->handle($request);
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
