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
            $login = $request->getParsedBody()['login'];
            $password = $request->getParsedBody()['password'];

            $this->usuario = Usuario::query()
                ->with('TipoUsuario')
                ->with('PerfilUsuario')
                ->where('situacao_usuario', 'A')
                ->where('cpf_usuario', $login)
                ->first();

            if ($this->usuario) {
                $this->usuario->contratos = Usuario::with('Lotacao')->where('cpf_usuario', $login)->where('situacao_usuario', 'A')->get()->toArray();
                if ($this->usuario->situacao_usuario == 'A') {
                    $checkUser = Usuario::query()
                        ->where('nascimento', $password)
                        ->get();

                    if ($checkUser->count() <= 0) {

                        return $this->respondWithError($response, ['errorMessage' => 'Login e/ou senha incorretos!']);
                    }
                    $this->addSession();

                    return $this->respondWithSuccess($response, ['successMessage' => 'Login efetuado com sucesso!']);
                } else {
                    return $this->respondWithError($response, ['errorMessage' => 'Login e/ou senha incorretos!']);
                }
            } else {
                return $this->respondWithError($response, ['errorMessage' => 'Login e/ou senha incorretos!']);
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
                    unset($_SESSION[APP_SIGLA_NAME]['user']);;
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
