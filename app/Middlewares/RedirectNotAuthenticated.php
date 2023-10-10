<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Utils\Auth;

class RedirectNotAuthenticated
{

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!Auth::logged()) {
            $response = $handler->handle($request);
            return $response->withRedirect(APP_URL . '/autenticacao/login');
        }

        $response = $handler->handle($request);

        return $response;
    }
}
