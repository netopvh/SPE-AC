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
		if (Auth::perfil_usuario()['id_tipo_perfil'] == 1) {

			$orgaos = Orgao::with('lotacao')
				//s->where('id_tipo_lotacao', '1')
				->get()
				->toArray();
		} else if (in_array((Auth::perfil_usuario())['id_tipo_perfil'], [2, 4])) {
			$ids_orgaos = OrgaoResponsavel::where('id_usuario', Auth::id_usuario())->pluck('id_orgao');

			$orgaos = Orgao::with('lotacao')
				//->where('id_tipo_lotacao', '1')
				->whereIn('id_orgao', $ids_orgaos)
				->get()
				->toArray();
			// var_dump($ids_orgaos); exit;
		} else {
			$orgaoUsuario = Auth::id_orgao_exercicio_usuario();

			$orgaos = Orgao::with('lotacao')
				//->where('id_tipo_lotacao', '1')
				->where('id_orgao', $orgaoUsuario)
				->get()
				->toArray();

			//var_dump($orgaos);
			//exit;
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


		if (($request->getParams())['id_lotacao']) {

			$orgao = Orgao::query()
				->with('lotacao')
				->where('id_orgao', ($request->getParams())['id_lotacao'])
				->get()
				->first()
				->toArray();
		} else {
			$orgaoUsuario = Auth::id_orgao_exercicio_usuario();

			$orgao = Orgao::query()
				->with('lotacao')
				->where('id_orgao', $orgaoUsuario)
				->get()
				->first()
				->toArray();
		}

		$itens = [];
		$n = 0;

		//RESPONSAVEL
		$responsavel = OrgaoResponsavel::where('id_orgao', $orgao['id_orgao'])->where('funcao', 1)->with('Usuario')->get()->toArray();

		foreach ($responsavel as $chave => $resp) {
			$nome_formatado = explode(" ", rtrim($responsavel[$chave]['usuario']['nome_usuario']));
			$responsavel[$chave]['nome_formatado'] = ($responsavel[$chave]['usuario']['nome_usuario']) ? $nome_formatado[0] . " " . end($nome_formatado) : 'RESPONSÁVEL NÃO CADASTRADO';
			$responsavel[$chave]['nome_usuario'] = trim($responsavel[$chave]['usuario']['nome_usuario']);
			$responsavel[$chave]['email_usuario'] = trim($responsavel[$chave]['usuario']['email_usuario']);
			$responsavel[$chave]['cargo_usuario'] = trim($responsavel[$chave]['usuario']['cargo_usuario']);
			$responsavel[$chave]['cargo_comissao_usuario'] = trim($responsavel[$chave]['usuario']['cargo_comissao_usuario']);
			$responsavel[$chave]['matricula_usuario'] = trim($responsavel[$chave]['usuario']['matricula_usuario']);
			$responsavel[$chave]['contrato_usuario'] = trim($responsavel[$chave]['usuario']['contrato_usuario']);
		}

		$dados = $orgao;
		$dados['responsaveis'] = $responsavel;
		$dados['nivel_pai'] = 0;

		$itens[$n] = $dados;
		$itens[$n]['children'] = $this->lotacoes($orgao);



		$resposta = [
			'dados' => $itens,
		];

		$payload = json_encode($resposta, JSON_PRETTY_PRINT);

		$response->getBody()->write($payload);

		return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
	}

	public function lotacoes($orgao)
	{
		$itens = [];
		$arrData = [];

		foreach ($orgao['lotacao'] as $lotacao) {

			//Hierarquia::query()->with('lotacaoSubordinada', 'dadosLotacao')	
			$lotacoes = Hierarquia::query()->join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao')
				->where('lotacao.id_lotacao', $lotacao['id_lotacao'])
				->where('lotacao.status_lotacao', 'A')
				->groupBy('lotacao.id_lotacao')
				->get()
				->toArray();

			if (count($lotacoes) >= 1) {

				$n = 0;

				foreach ($lotacoes as $dados) {

					//RESPONSAVEL
					$Responavel = LotacaoResponsavel::where('id_lotacao', $dados['id_lotacao'])->where('status_lotacao_responsavel', 'A')->with('Usuario')->get()->toArray();

					foreach ($Responavel as $chave => $resp) {
						$nome_formatado = explode(" ", rtrim($Responavel[$chave]['usuario']['nome_usuario']));
						$Responavel[$chave]['nome_formatado'] = ($Responavel[$chave]['usuario']['nome_usuario']) ? $nome_formatado[0] . " " . end($nome_formatado) : 'RESPONSÁVEL NÃO CADASTRADO';
						$Responavel[$chave]['nome_usuario'] = trim($Responavel[$chave]['usuario']['nome_usuario']);
						$Responavel[$chave]['email_usuario'] = trim($Responavel[$chave]['usuario']['email_usuario']);
						$Responavel[$chave]['cargo_usuario'] = trim($Responavel[$chave]['usuario']['cargo_usuario']);
						$Responavel[$chave]['cargo_comissao_usuario'] = trim($Responavel[$chave]['usuario']['cargo_comissao_usuario']);
						$Responavel[$chave]['matricula_usuario'] = trim($Responavel[$chave]['usuario']['matricula_usuario']);
						$Responavel[$chave]['contrato_usuario'] = trim($Responavel[$chave]['usuario']['contrato_usuario']);
					}

					$itens = $dados;
					$itens['nivel_pai'] = 1;
					$itens['responsaveis'] = $Responavel;
					$itens['children'] = $this->subordinados($this->retornaSubordinada($dados['id_lotacao']));


					array_push($arrData, $itens);

					$n++;
				}
			}
		}

		return $arrData;
	}

	public function subordinados($idLotacao)
	{

		$itens = [];
		$arrData = [];

		$lotacoes = Hierarquia::query()->join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao_subordinada')
			->where('lotacao.status_lotacao', 'A')
			->whereIn('lotacao.id_lotacao', $idLotacao)
			->get()
			->toArray();

		if (count($lotacoes) >= 1) {

			$n = 0;
			foreach ($lotacoes as $dados) {

				//RESPONSAVEL
				$Responavel = LotacaoResponsavel::where('id_lotacao', $dados['id_lotacao'])->where('status_lotacao_responsavel', 'A')->with('Usuario')->get()->toArray();

				foreach ($Responavel as $chave => $resp) {
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
				$dados['nivel_pai'] = 2;

				$itens[$n] = $dados;
				//$itens[$n]['children'] = $this->subordinados($dados['id_lotacao_subordinada']);
				$n++;
			}
		}

		return $itens;
	}

	private function retornaSubordinada($lotacao)
	{
		$subordinadas = Hierarquia::query()
			->select('hierarquia.id_lotacao_subordinada')
			->join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao')
			->where('lotacao.id_lotacao', $lotacao)
			->where('lotacao.status_lotacao', 'A')
			->get()
			->pluck('id_lotacao_subordinada')
			->toArray();

		if (count($subordinadas) >= 1) {
			return $subordinadas;
		}
		return null;
	}
}
