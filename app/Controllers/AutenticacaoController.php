<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Extensions\Support\Hash;
use App\Extensions\Support\Env;

use App\Models\{
    Usuario
};

use App\Classes\LDAP;
use App\Utils\Auth;

class AutenticacaoController extends Controller
{
    protected $usuario;

    public function login(Request $request, Response $response)
    {
        if ($request->getMethod() == 'POST') {
            $login = explode('@', ($request->getParams())['login'])[0];
            $login = $login . '@ac.gov.br';
            $this->usuario = Usuario::with('TipoUsuario')->with('PerfilUsuario')->where('situacao_usuario', 'A')->where('email_usuario', $login)->first();
            if ($this->usuario) {
                $this->usuario->contratos = Usuario::with('Lotacao')->where('email_usuario', $login)->where('situacao_usuario', 'A')->get()->toArray();
                if ($this->usuario->situacao_usuario == 'A') {
                    $ldap = new LDAP();
                    $ldap->setLogin(explode('@', ($request->getParams())['login'])[0]);
                    $ldap->setPassword(($request->getParams())['password']);

                    if ($ldap->verify_login()) {
                        $this->addSession();

                        return $response->withStatus(200)->withJson([]);
                    } else {
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Senha incorreta!']);
                    }

                    return $response->withStatus(200)->withJson(['message' => 'Login realizado com sucesso.']);
                } else {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Usuário Bloqueado!']);
                }
            } else {
                return $response->withStatus(404)->withJson(['errorMessage' => 'Login e/ou senha incorretos!']);
            }
        }


        return $this->view(
            $response,
            'auth',
            'login'
        );

    }

    public function alterar(Request $request, Response $response)
    {
        try {

            $usuario = Usuario::find(Auth::id_usuario());
            $this->usuario = Usuario::with('TipoUsuario')->with('PerfilUsuario')->where('email_usuario', $usuario->email_usuario)->where('id_usuario', '!=', $usuario->id_usuario)->first();
            if ($this->usuario) {
                if ($this->usuario->situacao_usuario == 'A') {
                    $this->usuario->contratos = Usuario::with('Lotacao')->where('email_usuario', $usuario->email_usuario)->get()->toArray();
                    session_start();
                    unset($_SESSION[APP_SIGLA_NAME]['user']);
                    ;
                    session_write_close();
                    $this->addSession();
                }
            }

            return $response->withRedirect(APP_URL . '/dashboard');
        } catch (\Throwable $th) {
            return $response->withRedirect(APP_URL . '/dashboard');
        }
    }

    protected function addSession()
    {
        //salva o registro na sessão
        session_start();
        $_SESSION[APP_SIGLA_NAME]['user'] = serialize($this->usuario->toArray());
        session_write_close();
    }

    public function logout(Request $request, Response $response)
    {
        session_start();
        unset($_SESSION[APP_SIGLA_NAME]['user']);
        session_write_close();

        return $response->withRedirect(
            APP_URL . '/autenticacao/login'
        );
    }
}