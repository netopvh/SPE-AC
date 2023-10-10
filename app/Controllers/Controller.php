<?php

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use App\Utils\Auth;
use App\Extensions\Support\Str;

abstract class Controller
{
    //contâiner de dependências
    protected $container;

    public function __construct(Container $container)
    {
        //recupera o container de dependências
        $this->container = $container;
    }

    public function view(Response $response, $folder, $file, $args = [])
    {
        //resolve o caminho das pastas
        $folder = str_replace('.', DIRECTORY_SEPARATOR, $folder);
        $file = str_replace('.', DIRECTORY_SEPARATOR, $file);

        //retorna um response com a view
        return $this->container->get('view')->render(
            $response,
            "pages/{$folder}/{$file}.twig",
            $args
        );
    }

    public function view_template(Response $response, $folder, $file, $args = [])
    {
        //resolve o caminho das pastas
        $folder = str_replace('.', DIRECTORY_SEPARATOR, $folder);
        $file = str_replace('.', DIRECTORY_SEPARATOR, $file);

        //retorna um response com a view
        return $this->container->get('view')->render(
            $response,
            "templates/{$folder}/{$file}.twig",
            $args
        );
    }

    protected function respondWithSuccess(Response $response, $payload = null, $status = 200)
    {
        $json = (string) json_encode($payload, JSON_PRETTY_PRINT);

        $response->getBody()->write($json);

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    protected function respondWithError(Response $response, $payload = null, $status = 404)
    {
        $json = (string) json_encode($payload, JSON_PRETTY_PRINT);

        $response->getBody()->write($json);

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
