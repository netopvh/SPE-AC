<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Models\{
    Hierarquia,
    Orgao,
    Lotacao,
    LotacaoResponsavel,
	OrgaoResponsavel
};

use App\Utils\Auth;

class OrganogramaController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {	
		if(Auth::perfil_usuario()['id_tipo_perfil'] == 1){			
			$orgaos = Lotacao::join('orgao', 'orgao.id_orgao', 'lotacao.id_orgao')->where('id_tipo_lotacao', '1')->orderBy('orgao.sigla_orgao')->get()->toArray();

        } else if(in_array((Auth::perfil_usuario())['id_tipo_perfil'], [2,4])){
			$ids_orgaos = OrgaoResponsavel::where('id_usuario', Auth::id_usuario())->pluck('id_orgao');

			$orgaos = Lotacao::join('orgao', 'orgao.id_orgao', 'lotacao.id_orgao')
			->where('id_tipo_lotacao', '1')
			->whereIn('orgao.id_orgao', $ids_orgaos)
			->orderBy('orgao.sigla_orgao')
			->get()
			->toArray();

			// var_dump($ids_orgaos); exit;
        } else {
			$ORGAO_USUARIO = Auth::id_orgao_exercicio_usuario();
			
			$orgaos = Lotacao::join('orgao', 'orgao.id_orgao', 'lotacao.id_orgao')->where('id_tipo_lotacao', '1')
						->where('lotacao.id_orgao', $ORGAO_USUARIO)
						->get()->toArray();
		}
        
        return $this->view(
            $response,
            'organograma',
            'index',
			[
				'orgaos' => $orgaos
			]
        );
    }
	
	public function exibir(Request $request, Response $response, $args)
	{
		
		
		if( ($request->getParams())['id_lotacao'] ){
			$orgao = Lotacao::where('id_tipo_lotacao', '1')
						->where('id_lotacao', ($request->getParams())['id_lotacao'] )
						->get()->first();
		} else {
			$ORGAO_USUARIO = Auth::id_orgao_exercicio_usuario();
			
			$orgao = Lotacao::where('id_tipo_lotacao', '1')
						->where('id_orgao', $ORGAO_USUARIO)
						->get()->first();
		}
		
		$Lotacoes = Hierarquia::Join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao')
					->where('lotacao.status_lotacao', 'A')
					->where('hierarquia.nivel_pai', 1)
					->where('lotacao.id_lotacao', $orgao['id_lotacao'])
					->groupBy('lotacao.id_lotacao')
					->get()->toArray();
		
		$Itens = [];
		$n = 0;
		
		foreach($Lotacoes as $dados){
			//RESPONSAVEL
			$Responavel = OrgaoResponsavel::where('id_orgao', $dados['id_orgao'])->where('funcao', 1)->with('Usuario')->get()->toArray();
			
			foreach($Responavel as $chave => $resp)
			{
				$nome_formatado = explode(" ", rtrim($Responavel[$chave]['usuario']['nome_usuario']));
				$Responavel[$chave]['nome_formatado'] = ($Responavel[$chave]['usuario']['nome_usuario']) ? $nome_formatado[0] . " " . end($nome_formatado) : 'RESPONSÁVEL NÃO CADASTRADO';
				$Responavel[$chave]['nome_usuario'] = trim($Responavel[$chave]['usuario']['nome_usuario']);
				$Responavel[$chave]['email_usuario'] = trim($Responavel[$chave]['usuario']['email_usuario']);
				$Responavel[$chave]['cargo_usuario'] = trim($Responavel[$chave]['usuario']['cargo_usuario']);
				$Responavel[$chave]['cargo_comissao_usuario'] = trim($Responavel[$chave]['usuario']['cargo_comissao_usuario']);
				$Responavel[$chave]['matricula_usuario'] = trim($Responavel[$chave]['usuario']['matricula_usuario']);
				$Responavel[$chave]['contrato_usuario'] = trim($Responavel[$chave]['usuario']['contrato_usuario']);
			}
			
			$dados['responsaveis'] = $Responavel;
			$dados['nivel_pai'] = 0;
			$Itens[$n] = $dados;
			$Itens[$n]['children'] = $this->subordinados($dados['id_lotacao']);
			$n++;
		}
		
		$resposta = [
            'dados' => $Itens,
        ];

        $payload = json_encode($resposta, JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
	
	public function subordinados($id_lotacao = NULL)
	{
		$Itens = [];
		
		$Lotacoes = Hierarquia::Join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao_subordinada')
					->where('lotacao.status_lotacao', 'A')
					->where(function($query) use ($id_lotacao) {
						if($id_lotacao){
							$query->where('hierarquia.id_lotacao', $id_lotacao);
						}
					})
					->get()->toArray();
		
		$n = 0;
		foreach($Lotacoes as $dados) {
			
			//RESPONSAVEL
			$Responavel = LotacaoResponsavel::where('id_lotacao', $dados['id_lotacao'])->where('status_lotacao_responsavel', 'A')->with('Usuario')->get()->toArray();
			
			foreach($Responavel as $chave => $resp)
			{
				$nome_formatado = explode(" ", rtrim($Responavel[$chave]['usuario']['nome_usuario']));
				$Responavel[$chave]['nome_formatado'] = ($Responavel[$chave]['usuario']['nome_usuario']) ? $nome_formatado[0] . " " . end($nome_formatado) : 'RESPONSÁVEL NÃO CADASTRADO';
				$Responavel[$chave]['nome_usuario'] = trim($Responavel[$chave]['usuario']['nome_usuario']);
				$Responavel[$chave]['email_usuario'] = trim($Responavel[$chave]['usuario']['email_usuario']);
				$Responavel[$chave]['cargo_usuario'] = trim($Responavel[$chave]['usuario']['cargo_usuario']);
				$Responavel[$chave]['cargo_comissao_usuario'] = trim($Responavel[$chave]['usuario']['cargo_comissao_usuario']);
				$Responavel[$chave]['matricula_usuario'] = trim($Responavel[$chave]['usuario']['matricula_usuario']);
				$Responavel[$chave]['contrato_usuario'] = trim($Responavel[$chave]['usuario']['contrato_usuario']);
			}
			
			$dados['responsaveis'] = $Responavel;
			
			$Itens[$n] = $dados;
			$Itens[$n]['children'] = $this->subordinados($dados['id_lotacao_subordinada']);
			$n++;
		}
		
		return $Itens;
	}
	
}