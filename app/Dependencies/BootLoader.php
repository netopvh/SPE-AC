<?php

namespace App\Dependencies;

use App\Utils\Auth;
use DI\Container;
use Slim\Views\Twig;
use App\Classes\MinhasLotacoes;
use App\Extensions\Support\Env;
use App\Extensions\Support\Str;
use App\Extensions\Support\Date;
use App\Middlewares\ViewMiddleware;
use App\Extensions\Support\FileSystem;

class BootLoader
{
    //contâiner de dependências
    protected Container $container;

    //tipo de usuário logado
    protected $tipo_usuario;

    //ecopo de usuário logado
    private $scope;

    public function __construct(&$container)
    {
        $this->container = $container;
    }

    public function boot()
    {
        $this->config();
        $this->views();
        $this->database();
        $this->container();
        $this->autoloading();
        // $this->middlewares();

        return $this->container;
    }

    protected function config()
    {
        date_default_timezone_set(APP_TIMEZONE);
    }

    protected function views()
    {
        $this->container->set('view', function () {

            $view = new Twig(
                FileSystem::directory('../resources/views'),
                ['cache' => false]
            );

            $view->getEnvironment()->addGlobal('AUTH', $this->container->get('auth'));
            $view->getEnvironment()->addGlobal('LOTACOES', $this->container->get('lotacoes'));
            $view->getEnvironment()->addGlobal('APP_URL', APP_URL);
            $view->getEnvironment()->addGlobal('TURMALINA', TURMALINA);
            $view->getEnvironment()->addGlobal('BASE_URL', BASE_URL);
            $view->getEnvironment()->addGlobal('APP_NAME', APP_NAME);
            $view->getEnvironment()->addGlobal('APP_SIGLA_NAME', APP_SIGLA_NAME);
            $view->getEnvironment()->addGlobal('DATE', new Date);
            $view->getEnvironment()->addGlobal('APP_VERSION', APP_VERSION);
            $view->getEnvironment()->addGlobal('str', new Str());
            $view->getEnvironment()->addGlobal('ENV', new Env());

            return $view;
        });
    }

    protected function database()
    {
        $this->container->set('db', function () {
            $capsule = new \Illuminate\Database\Capsule\Manager;
            $capsule->addConnection(APP_DATABASE);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        });
    }

    protected function container()
    {
        $this->container->set('auth', function () {
            return new Auth();
        });
        $this->container->set('lotacoes', function () {
            return new MinhasLotacoes();
        });
    }

    protected function autoloading()
    {
        Filesystem::autoload("app\\Controllers\\", $this->container);
    }

    protected function middlewares()
    {

        $this->container->get('view')->getEnvironment()->addGlobal('AUTH', $this->container->get('auth'));

        $this->container->get('view')->getEnvironment()->addGlobal('LOTACOES', $this->container->get('lotacoes'));

        $this->container->get('view')->getEnvironment()->addGlobal('APP_URL', APP_URL);
        $this->container->get('view')->getEnvironment()->addGlobal('APP_NAME', APP_NAME);
        $this->container->get('view')->getEnvironment()->addGlobal('APP_SIGLA_NAME', APP_SIGLA_NAME);
        $this->container->get('view')->getEnvironment()->addGlobal('DATE', new Date);
        $this->container->get('view')->getEnvironment()->addGlobal('APP_VERSION', APP_VERSION);
        $this->container->get('view')->getEnvironment()->addGlobal('str', new Str());
        $this->container->get('view')->getEnvironment()->addGlobal('ENV', new Env());
    }
}