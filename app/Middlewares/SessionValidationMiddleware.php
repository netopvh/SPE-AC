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

    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        if (Routing::out($request, $this->open)) {
            $response = $handler->handle($request);
            return $response;
        } else {
            if ($_SERVER['REQUEST_URI'] == '/spe_novo/') {
                // if ($_SERVER['REQUEST_URI'] == '/') {
                $response = $handler->handle($request);
                return $response;
            } else {
                if (Auth::logged()) {
                    $response = $handler->handle($request);
                    return $response;
                } else {
                    $response = $handler->handle($request);
                    return $response->withRedirect(APP_URL . '/autenticacao/login');
                }
            }
        }

        $response = $handler->handle($request);
        return $response->withRedirect(APP_URL . '/autenticacao/login');
    }
}