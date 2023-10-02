<?php

namespace App\Services;

use App\Extensions\Support\FileSystem;
use App\Models\Faltas;
use App\Models\Importacao;
use App\Models\Lotacao;
use App\Models\Orgao;
use App\Utils\ConectionTurmalina;
use PDO;
use PDOException;

class Conection
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            try {
                self::$instance = new PDO('mysql:host=' . APP_DATABASE['host'] . ';dbname=' . APP_DATABASE['database'] . ';', APP_DATABASE['username'], APP_DATABASE['password']);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }
        return self::$instance;
    }

    public static function prepare($sql)
    {
        return self::getInstance()->prepare($sql);
    }
}

class ImportacaoService
{
    private bool $turmalina;

    private string $fileType;

    private string $dsn;

    public function __construct()
    {
        $this->turmalina = TURMALINA;
    }

    public function importar(int $codOrgao = 0)
    {
        try {
            if ($this->verificarImportacao()) {
                //$this->iniciarImportacao();

                $orgaos = $this->getOrgaos($codOrgao);

                foreach ($orgaos as $orgao) {
                    $this->verificarOrgao(
                        $orgao[0],
                        str_replace("'", "", $orgao[1]),
                    );

                    $lotacoes = $this->getLotacoes($orgao[0]);
                    $codLotacoes = [];

                    foreach ($lotacoes as $lotacao) {
                        $codLotacoes[] = $lotacao[0];

                        $this->verificarLotacao(
                            $lotacao[0],
                            $lotacao[3],
                            str_replace("'", "", $lotacao[1]),
                            $lotacao[2]
                        );
                    }

                    $this->removeLotacoes($codLotacoes, $orgao[0]);

                    //$usuarios = $this->getUsuarios($orgao[0]);

                    //echo '<pre>';
                    //var_dump($usuarios);
                    //echo '</pre>';
                }

                //verificarFaltas();

                //verificarHierarquia();

                //verificarAfastamentoTemporarios();

                $this->pararImportacao();
            } else {
                print 'Existe uma importação em progresso!';
            }
        } catch (\Throwable $th) {
            $this->iniciarErroImportacao($th->getMessage() . ' - Line: ' .  $th->getLine());
            print $th->getMessage() . ' - Line: ' .  $th->getLine();
        }
    }

    /**
     * Informa o tipo de informação que será importada
     *
     * @param string $fileType
     * @param txt | xml | csv
     * @return void
     */
    public function setFileType($fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function setDsn(string $dns)
    {
        $this->dsn = $dns;

        return $this;
    }

    public function verificarImportacao(): bool
    {
        $importacao = Importacao::query()->where('situacao_importacao', 'S')->first();

        if ($importacao) {
            return false;
        }

        return true;
    }

    public function iniciarImportacao()
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
        $situacao_importacao = 'S';

        Importacao::query()->create([
            'situacao_importacao' => $situacao_importacao,
            'data_criacao_importacao' => $data_insercao_atualizacao,
            'data_atualizacao_importacao' => $data_insercao_atualizacao,
        ]);

        return true;
    }

    private function iniciarErroImportacao($erro)
    {
        $sql = "INSERT INTO erro_importacao ( descricao_erro_importacao, data_criacao_erro_importacao, data_atualizacao_erro_importacao ) VALUES ( :descricao_erro_importacao, :data_criacao_erro_importacao, :data_atualizacao_erro_importacao )";
        $stm = Conection::prepare($sql);
        $stm->bindParam(':descricao_erro_importacao', $erro, PDO::PARAM_STR);
        $stm->bindParam(':data_criacao_erro_importacao', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stm->bindParam(':data_atualizacao_erro_importacao', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stm->execute();

        $this->pararImportacao();

        return true;
    }

    private function pararImportacao()
    {
        Importacao::query()->where('situacao_importacao', 'S')->update([
            'situacao_importacao' => 'N',
            'data_atualizacao_importacao' => DATA_INSERCAO_ATUALIZACAO,
        ]);

        return true;
    }

    public function getOrgaos($codOrgao)
    {
        $fileDir = $this->makeFile('orgaos');

        $query = '';
        if ($codOrgao !== 0) {
            $query = " WHERE i_entidades = $codOrgao";
        }

        $stSql = "SELECT  i_entidades, apelido FROM bethadba.entidades$query; OUTPUT TO " . $fileDir . ";";

        $this->makeCommand($stSql);
        return $this->decodeFile($fileDir);
    }

    private function insertOrgao($id_orgao, $descricao_orgao)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        Orgao::query()->create([
            'id_orgao' => $id_orgao,
            'descricao_orgao' => $descricao_orgao,
            'data_criacao_orgao' => $data_insercao_atualizacao,
            'data_atualizacao_orgao' => $data_insercao_atualizacao,
        ]);

        return true;
    }

    private function updateOrgao($id_orgao, $descricao_orgao)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $orgao = Orgao::query()->where('id_orgao', $id_orgao)->first();

        $orgao->id_orgao = $id_orgao;
        $orgao->descricao_orgao = $descricao_orgao;
        $orgao->data_atualizacao_orgao = $data_insercao_atualizacao;
        $orgao->save();

        return true;
    }

    public function getLotacoes($cod_orgao)
    {
        $fileDir = $this->makeFile('lotacoes');

        $stSql = 'SELECT i_locais_trab, nome, i_cidades, i_entidades FROM bethadba.locais_trab WHERE i_entidades = ' . $cod_orgao . '; OUTPUT TO ' . $fileDir . ';';

        $this->makeCommand($stSql);

        return $this->decodeFile($fileDir);
    }

    function verificarLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao)
    {
        $lotacao = Lotacao::query()->where('id_lotacao', $id_lotacao)->get(['id_lotacao']);
        $count = $lotacao->count();

        if ($count) {
            $this->updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao);
        } else {
            $this->insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao);
        }

        return true;
    }

    private function insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao): void
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        Lotacao::query()->create([
            'id_lotacao' => $id_lotacao,
            'id_orgao' => $id_orgao,
            'descricao_lotacao' => $descricao_lotacao,
            'municipio_lotacao' => $municipio_lotacao,
            'data_criacao_lotacao' => $data_insercao_atualizacao,
            'data_atualizacao_lotacao' => $data_insercao_atualizacao,
        ]);
    }

    private function updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao): void
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        Lotacao::query()->where('id_lotacao', $id_lotacao)->update([
            'id_lotacao' => $id_lotacao,
            'id_orgao' => $id_orgao,
            'descricao_lotacao' => $descricao_lotacao,
            'municipio_lotacao' => $municipio_lotacao,
            'data_atualizacao_lotacao' => $data_insercao_atualizacao,
        ]);
    }

    private function removeLotacoes($codLocacoes, $id_orgao)
    {
        if ($codLocacoes) {
            $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

            Lotacao::query()->whereNotIn('id_lotacao', $codLocacoes)->where('id_orgao', $id_orgao)->update([
                'status_lotacao' => 'R',
                'data_atualizacao_lotacao' => $data_insercao_atualizacao,
            ]);
        }
    }

    private function verificarOrgao($id_orgao, $descricao_orgao)
    {

        $orgaos = Orgao::query()->where('id_orgao', $id_orgao)->get(['id_orgao']);
        $count = $orgaos->count();

        if ($count) {
            $this->updateOrgao($id_orgao, $descricao_orgao);
        } else {
            $this->insertOrgao($id_orgao, $descricao_orgao);
        }

        return true;
    }

    private function getUsuarios($codOrgao)
    {
        $fileDir = $this->makeFile('usuarios');

        $stSql = 'SELECT i_funcionarios, cpf, nome, dt_admissao, (SELECT TOP 1 nome FROM bethadba.hist_cargos KEY JOIN bethadba.cargos WHERE hist_cargos.i_funcionarios = funcionarios.i_funcionarios AND i_tipos_cargos = 1 ORDER BY dt_alteracoes DESC) AS cargo_carreira,' .
            '(SELECT TOP 1 nome FROM bethadba.hist_cargos KEY JOIN bethadba.cargos WHERE hist_cargos.i_funcionarios = funcionarios.i_funcionarios AND i_tipos_cargos > 1 ORDER BY dt_alteracoes DESC) AS cargo_comissao, ' .
            'funcionarios.i_entidades AS cod_orgao_exercicio, ' .
            '(SELECT TOP 1 i_locais_trab FROM bethadba.locais_mov WHERE locais_mov.i_entidades = funcionarios.i_entidades AND locais_mov.i_funcionarios = funcionarios.i_entidades AND dt_final is null) AS cod_lotacao_exercicio, ' .
            '(SELECT TOP 1 email FROM bethadba.hist_pessoas_fis WHERE i_pessoas=1 ORDER BY dt_alteracoes DESC) AS email FROM bethadba.funcionarios JOIN bethadba.hist_pessoas_fis ON hist_pessoas_fis.i_pessoas = funcionarios.i_pessoas JOIN bethadba.pessoas ON pessoas.i_pessoas = hist_pessoas_fis.i_pessoas ' .
            'WHERE funcionarios.i_entidades = ' . $codOrgao . ';' .
            'OUTPUT TO ' . $fileDir . ';';

        $this->makeCommand($stSql);

        return $this->decodeFile($fileDir);
    }

    public function makeCommand(string $sqlCommand)
    {
        $stComando = "\"%ASANY9%/win32/dbisql.exe\" -nogui -datasource " . $this->dsn . " -codepage utf8 -q \"" . $sqlCommand . "\" ";

        exec($stComando);
    }

    private function removeFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function decodeFile(string $fileDir): array
    {

        $data = str_getcsv(file_get_contents($fileDir), "\n");

        $csv = array_map('str_getcsv', $data);

        //$this->removeFile($fileDir);

        return $csv;
    }

    private function makeFile(string $fileName): string
    {
        $path = FileSystem::makeStorageDir('generated', true);
        $definedFileName = $fileName . ".csv";
        $fileDir = $path . DIRECTORY_SEPARATOR . $definedFileName;

        return $fileDir;
    }
}
