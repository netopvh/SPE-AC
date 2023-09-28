<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RedirectMainRoute
{
    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        if ($_SERVER['REQUEST_URI'] == '/') {
            $response = $handler->handle($request);
            return $response->withRedirect(APP_URL . '/autenticacao/login');
        }

        $response = $handler->handle($request);

        return $response;
    }
}