<?php

namespace App\Services;

use App\Models\Faltas;
use App\Models\Importacao;
use App\Models\Orgao;
use App\Utils\ConectionTurmalina;

class ImportacaoService
{
    private boolean $turmalina;

    private string $fileType;

    public function __construct()
    {
        $this->turmalina = TURMALINA;
    }

    /**
     * Informa o tipo de informação que será importada
     *
     * @param string $fileType
     * @param txt | xml | csv
     * @return void
     */
    public function setFileType($fileType) : self
    {
        $this->fileType = $fileType;

        return self;
    }

    public function verificarImportacao()
    {
        $importacao = Importacao::where('situacao_importacao', 'S')->first();

        if ($importacao) {
            return false;
        }

        return true;
    }

    public function iniciarImportacao()
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
        $situacao_importacao = 'S';

        $result = Importacao::query()->create([
            'situacao_importacao' => $situacao_importacao,
            'data_criacao_importacao' => $data_insercao_atualizacao,
            'data_atualizacao_importacao' => $data_insercao_atualizacao,
        ]);

        return true;
    }

    public function getOrgaos()
    {
        if ($this->turmalina) {
            $query = '';
            if ($cod_orgao) {
                $query = " AND COD_ORGAO = $cod_orgao ";
            }

            $sql = "SELECT COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO FROM VW_LOTACAO WHERE NOT (COD_ORGAO IN (1,61,70,91)) $query GROUP BY COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO";
            $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
            oci_execute($stid);
            $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

            return ['data' => $data, 'rowCount' => $rowCount];
        } else {
            $orgaos = Orgao::select('id_orgao', 'nome_orgao', 'sigla_orgao')->whereNotIn('id_orgao', [1, 61, 70, 91])->get();

            return ['data' => $orgaos, 'rowCount' => $orgaos->count()];
        }
    }

    public function verificarFaltas()
    {
        if (this->turmalina && $this->fileType === 'csv') {
            $sql = "SELECT * FROM vw_faltas";
            $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
            oci_execute($stid);
            $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

            if ($data) {
                $limpar = "TRUNCATE TABLE faltas";
                $stm = Conection::prepare($limpar);
                $stm->execute();
            }
        } else if ($this->fileType === 'txt') {
            $data = file_get_contents('http://www.transparencia.mg.gov.br/ckan/dataset/0b0b0b0b-0b0b-0b0b-0b0b-0b0b0b0b0b0b/resource/0b0b0b0b-0b0b-0b0b-0b0b-0b0b0b0b0b0b/download/faltas.txt');
            $data = explode("\n", $data);
            $data = array_map(function ($value) {
                return explode(';', $value);
            }, $data);
            $data = array_filter($data);
            $data = array_values($data);
            $data = array_map(function ($value) {
                return array_combine(['MATRICULA', 'CONTRATO', 'DATA_INICIO_FALTA', 'DATA_FIM_FALTA', 'DESCRICAO_LANCAMENTO', 'QTD_DIAS'], $value);
            }, $data);
        }
        // else if ($this->fileType === 'xml') {
        //     $data = file_get_contents('http://www.transparencia.mg.gov.br/ckan/dataset/0b0b0b0b-0b0b-0b0b-0b0b-0b0b0b0b0b0b/resource/0b0b0b0b-0b0b-0b0b-0b0b-0b0b0b0b0b0b/download/faltas.xml');
        //     $data = simplexml_load_string($data);
        //     $data = json_encode($data);
        //     $data = json_decode($data, true);
        //     $data = $data['faltas']['falta'];
        // }

        $this->insereFaltas($data);

        return true;
    }

    private function insereFaltas($data)
    {
        if ($this->turmalina) {
            foreach ($data as $dados) {
                Faltas::query()->create([
                    'tipo' => trim($dados['DESCRICAO_LANCAMENTO']),
                    'qtd_dias' => intval($dados['QTD_DIAS']),
                    'matricula_usuario' => intval($dados['MATRICULA']),
                    'contrato' => intval($dados['CONTRATO']),
                    'data_inicio' => date('Y-m-d', strtotime($dados['DATA_INICIO_FALTA'])),
                    'data_termino' => date('Y-m-d', strtotime($dados['DATA_FIM_FALTA'])),
                ]);
            }
        } else {
            foreach ($data as $dados) {
                var_dump($dados);
            }
        }
    }
}