<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Utils\Auth;
use App\Extensions\Support\Routing;
use App\Models\Usuario;

class SessionValidationMiddleware
{

    //rotas sem atualização de sessão
    protected $open = ['/autenticacao', '/pontos', '/api'];

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (Routing::out($request, $this->open)) {

            if ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/spe') {
                $response = $handler->handle($request);
                return $response->withRedirect(APP_URL);
            }

            $response = $handler->handle($request);
            return $response;
        } else {

            if ($_SERVER['REQUEST_URI'] == '/spe_novo/' || $_SERVER['REQUEST_URI'] == '/spe_novo') {
                $response = $handler->handle($request);
                return $response;
            }

            $response = $handler->handle($request);
            return $response;
        }

        $response = $handler->handle($request);
        return $response->withRedirect(APP_URL);
    }
}
