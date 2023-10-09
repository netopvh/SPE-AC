<?php

namespace App\Classes;

use App\Utils\Auth;
use App\Models\{
	Hierarquia,
	Lotacao,
	LotacaoResponsavel
};

class MinhasLotacoes
{

	public static function lotacoes()
	{
		$MinhasLotacoes = [];
		$Lotacoes = LotacaoResponsavel::where('id_usuario', Auth::id_usuario())
			->orderBy('status_lotacao_responsavel', 'ASC')
			->get()->toArray();


		foreach ($Lotacoes as $dados) {
			if (!$MinhasLotacoes[$dados['id_lotacao']]) {
				$MinhasLotacoes[$dados['id_lotacao']] = $dados;
			}

			$MinhasLotacoes += MinhasLotacoes::subordinadas($dados['id_lotacao'], $dados['status_lotacao_responsavel'], $dados['data_criacao_lotacao_responsavel'], $dados['data_atualizacao_lotacao_responsavel']);
		}

		return $MinhasLotacoes;
	}

	public static function subordinadas($id_lotacao = NULL, $status_lotacao_responsavel = 'A', $data_criacao_lotacao_responsavel = NULL, $data_atualizacao_lotacao_responsavel = NULL)
	{
		$Itens = [];

		$Lotacoes = Hierarquia::Join('lotacao', 'lotacao.id_lotacao', '=', 'hierarquia.id_lotacao_subordinada')
			// ->where('lotacao.status_lotacao', 'A')
			->where(function ($query) use ($id_lotacao) {
				if ($id_lotacao) {
					$query->where('hierarquia.id_lotacao', $id_lotacao);
				}
			})
			->get()->toArray();

		foreach ($Lotacoes as $dados) {

			$Itens[$dados['id_lotacao']] = [
				"status_lotacao_responsavel" => $status_lotacao_responsavel,
				"data_atualizacao_lotacao_responsavel" => $data_atualizacao_lotacao_responsavel,
				"data_criacao_lotacao_responsavel" => $data_criacao_lotacao_responsavel,
				"id_lotacao" => $dados['id_lotacao']
			];
			$Itens += MinhasLotacoes::subordinadas($dados['id_lotacao_subordinada'], $status_lotacao_responsavel, $data_criacao_lotacao_responsavel, $data_atualizacao_lotacao_responsavel);
		}

		return $Itens;
	}

	static function lotacoesArray()
	{
		$Lotacoes = [];
		$MinhasLotacoes = MinhasLotacoes::lotacoes();

		foreach ($MinhasLotacoes as $dados) {
			if ($dados['status_lotacao_responsavel'] == 'A') {
				$Lotacoes[] = $dados['id_lotacao'];
			}
		}

		return $Lotacoes;
	}
}
