<?php

namespace App\Services;

use App\Extensions\Support\FileSystem;
use App\Models\Afastamento;
use App\Models\Faltas;
use App\Models\Ferias;
use App\Models\Importacao;
use App\Models\Lotacao;
use App\Models\Orgao;
use App\Models\Usuario;
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

    private string $fileType;

    private string $dsn;

    public function importar(int $codOrgao = 0)
    {
        try {
            if ($this->verificarImportacao()) {
                $this->iniciarImportacao();

                $orgaos = $this->getOrgaos($codOrgao);

                foreach ($orgaos as $orgao) {
                    $this->verificarOrgao(
                        $orgao[0],
                        $orgao[1]
                    );

                    $lotacoes = $this->getLotacoes($orgao[0]);
                    $codLotacoes = [];

                    foreach ($lotacoes as $lotacao) {
                        $codLotacoes[] = $this->verificarLotacao(
                            $lotacao[0],
                            $lotacao[3],
                            $lotacao[1],
                            $lotacao[2]
                        );
                    }

                    $this->removeLotacoes($codLotacoes, $orgao[0]);

                    $usuarios = $this->getUsuarios($orgao[0]);

                    foreach ($usuarios as $usuario) {
                        $this->verificaUsuario(
                            $usuario[0], //id_orgao_exercicio_usuario
                            $usuario[12],  //id_lotacao_execicio_usuario
                            $usuario[1], //id_usuario
                            $usuario[8], //cpf_usuario
                            $usuario[2], //nome_usuario
                            $usuario[4], //cargo_usuario
                            $usuario[3], //data_admissao_usuario
                            $usuario[11], //regime_usuario
                            $usuario[7] //situacao_funcional_usuario
                        );
                    }

                    $feriasData = $this->getFerias($orgao[0]);
                    $codFerias = [];

                    foreach ($feriasData as $ferias) {
                        $codFerias[] = $this->verificarFerias(
                            $ferias[2], //matricula_ferias
                            $ferias[3], //data_inicio_ferias
                            $ferias[4], //data_fim_ferias
                            $ferias[5] //qtd_dias_ferias
                        );
                    }

                    $this->removeFerias($codFerias, $orgao[0]);

                    $afastamentos = $this->getAfastamentos($orgao[0]);
                    $codAfastamentos = [];


                    foreach ($afastamentos as $afastamento) {
                        $codAfastamentos[] = $this->verificarAfastamentos(
                            $afastamento[0], //orgao
                            $afastamento[1], //matricula_afastamento
                            $afastamento[2], //descricao_afastamento
                            $afastamento[4], //data_inicio_afastamento
                            $afastamento[5], //data_fim_afastamento
                            $afastamento[6] //qtd_dias_afastamento
                        );
                    }

                    $this->removeAfastamentos($codAfastamentos, $orgao[0]);
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
        }
    }
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
        $sql = "INSERT INTO erro_importacao (descricao_erro_importacao, data_criacao_erro_importacao, data_atualizacao_erro_importacao ) VALUES ( :descricao_erro_importacao, :data_criacao_erro_importacao, :data_atualizacao_erro_importacao )";
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

    private function verificarOrgao($id_orgao, $descricao_orgao)
    {

        $orgaos = Orgao::query()->where('id_orgao', $id_orgao)->get(['id_orgao']);

        if ($orgaos->count()) {
            return $this->updateOrgao($id_orgao, $descricao_orgao);
        }

        return $this->insertOrgao($id_orgao, $descricao_orgao);;
    }

    private function insertOrgao($id_orgao, $descricao_orgao)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        Orgao::query()->create([
            'id_orgao' => $id_orgao,
            'descricao_orgao' => $this->limparAspas($descricao_orgao),
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
        $orgao->descricao_orgao = $this->limparAspas($descricao_orgao);
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

        if ($lotacao->count()) {
            return $this->updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao);
        }

        return $this->insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao);
    }

    private function insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $lotacao = Lotacao::create([
            'id_lotacao' => $id_lotacao,
            'id_orgao' => $id_orgao,
            'descricao_lotacao' => $this->limparAspas($descricao_lotacao),
            'municipio_lotacao' => $municipio_lotacao,
            'data_criacao_lotacao' => $data_insercao_atualizacao,
            'data_atualizacao_lotacao' => $data_insercao_atualizacao,
        ]);

        return $lotacao->id_lotacao;
    }

    private function updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $municipio_lotacao)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $lotacao = Lotacao::query()->where('id_lotacao', $id_lotacao)->first();
        $lastId = $lotacao->id_lotacao;

        $lotacao->update([
            'id_lotacao' => $id_lotacao,
            'id_orgao' => $id_orgao,
            'descricao_lotacao' => $this->limparAspas($descricao_lotacao),
            'municipio_lotacao' => $municipio_lotacao,
            'data_atualizacao_lotacao' => $data_insercao_atualizacao,
        ]);

        return $lastId;
    }

    private function removeLotacoes($codLotacoes, $id_orgao)
    {
        if ($codLotacoes) {
            $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

            $locacao = Lotacao::query()->where('id_orgao', $id_orgao)->whereNotIn('id_lotacao', $codLotacoes)->get();

            foreach ($locacao as $lotacao) {
                $lotacao->update([
                    'status_lotacao' => 'R',
                    'data_atualizacao_lotacao' => $data_insercao_atualizacao,
                ]);
            }
        }
    }

    private function getUsuarios($codOrgao)
    {
        $fileDir = $this->makeFile('usuarios');

        $stSql = "SELECT distinct funcionarios.i_entidades as orgao, i_funcionarios = bethadba.funcionarios.i_funcionarios," .
            "pessoas_nome = bethadba.pessoas.nome, funcionarios_dt_admissao = bethadba.funcionarios.dt_admissao," .
            "cargos_nome = bethadba.cargos.nome, dt_fim_cpt = SECONDS(DAYS(CAST( today() AS datetime ),1),-1)," .
            "situacao = isnull(bethadba.dbf_gettipoafast(1, funcionarios.i_entidades, funcionarios.i_funcionarios, bethadba.dbf_getdataafast(1, funcionarios.i_entidades, funcionarios.i_funcionarios, today(), 'S'), 'S'), 1)," .
            "situacao_funcional = if situacao = 1 then 'Trabalhando' else if situacao between 2 and 7 or situacao between 10 and 21 then 'Afastado' else if situacao = 8 then 'Demitido' else if situacao = 9 then 'Aposentado' endif endif endif endif," .
            "pessoas_fisicas.cpf, tipo_cargo = (SELECT descricao from bethadba.tipos_cargos t where t.i_tipos_cargos = cargos.i_tipos_cargos), " .
            "hist_funcionarios.i_vinculos, desc_vinculo = (select descricao from bethadba.vinculos where vinculos.i_vinculos=hist_funcionarios.i_vinculos), " .
            "lotacao = isnull((select top 1 i_locais_trab from bethadba.locais_mov where locais_mov.i_entidades = funcionarios.i_entidades AND locais_mov.i_funcionarios = funcionarios.i_funcionarios AND locais_mov.dt_final is null order by dt_inicial desc), " .
            "(select top 1 i_locais_trab from bethadba.locais_mov where locais_mov.i_entidades = funcionarios.i_entidades AND locais_mov.i_funcionarios = funcionarios.i_funcionarios AND locais_mov.dt_final is not null order by dt_final desc))," .
            "desc_lotacao=(select nome from bethadba.locais_trab where locais_trab.i_locais_trab=lotacao) " .
            "FROM bethadba.funcionarios, bethadba.pessoas, bethadba.pessoas_fisicas LEFT OUTER JOIN bethadba.pessoas_fis_compl ON(pessoas_fisicas.i_pessoas = pessoas_fis_compl.i_pessoas)," .
            "bethadba.cargos LEFT OUTER JOIN bethadba.cargos_compl ON (cargos.i_entidades = cargos_compl.i_entidades AND cargos.i_cargos = cargos_compl.i_cargos)," .
            "bethadba.hist_cargos, bethadba.hist_funcionarios WHERE funcionarios.i_pessoas = pessoas_fisicas.i_pessoas AND pessoas_fisicas.i_pessoas = pessoas.i_pessoas AND " .
            "cargos.i_entidades = hist_cargos.i_entidades AND cargos.i_cargos = hist_cargos.i_cargos AND hist_cargos.i_entidades = funcionarios.i_entidades AND hist_cargos.i_funcionarios = funcionarios.i_funcionarios AND " .
            "hist_cargos.dt_alteracoes = bethadba.dbf_getdatahiscar(funcionarios.i_entidades,funcionarios.i_funcionarios,dt_fim_cpt) AND hist_funcionarios.i_entidades = funcionarios.i_entidades AND " .
            "hist_funcionarios.i_funcionarios = funcionarios.i_funcionarios AND hist_funcionarios.dt_alteracoes = bethadba.dbf_getdatahisfun(funcionarios.i_entidades,funcionarios.i_funcionarios,dt_fim_cpt)" .
            "" . ($codOrgao !== 0 ? " AND funcionarios.i_entidades = $codOrgao" : "") . ";" .
            "OUTPUT TO " . $fileDir . ";";

        $this->makeCommand($stSql);

        return $this->decodeFile($fileDir);
    }

    private function verificaUsuario($id_orgao, $id_lotacao, $id_usuario, $cpf_usuario, $nome_usuario, $cargo_usuario, $data_admissao_usuario, $regime_usuario, $situacao_funcional_usuario)
    {

        $usuario = Usuario::query()->where('id_usuario', $id_usuario)->get(['id_usuario']);

        if ($usuario->count()) {
            return $this->updateUsuario($id_orgao, $id_lotacao, $id_usuario, $cpf_usuario, $nome_usuario, $cargo_usuario, $data_admissao_usuario, $regime_usuario, $situacao_funcional_usuario);
        }

        return $this->insertUsuario($id_orgao, $id_lotacao, $id_usuario, $cpf_usuario, $nome_usuario, $cargo_usuario, $data_admissao_usuario, $regime_usuario, $situacao_funcional_usuario);
    }

    private function updateUsuario($id_orgao, $id_lotacao, $id_usuario, $cpf_usuario, $nome_usuario, $cargo_usuario, $data_admissao_usuario, $regime_usuario, $situacao_funcional_usuario)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $usuario = Usuario::query()->where('id_usuario', $id_usuario)->first();

        $usuario->id_orgao_exercicio_usuario = $id_orgao;
        $usuario->id_lotacao_exercicio_usuario = $id_lotacao;
        $usuario->id_usuario = $id_usuario;
        $usuario->id_tipo_usuario = $this->checkTipoUsuario($regime_usuario);
        $usuario->cpf_usuario = $this->limparAspas($cpf_usuario);
        $usuario->nome_usuario = $this->limparAspas($nome_usuario);
        $usuario->cargo_usuario = $this->limparAspas($cargo_usuario);
        $usuario->matricula_usuario = $id_usuario;
        $usuario->data_admissao_usuario = $this->limparAspas($data_admissao_usuario);
        $usuario->regime_usuario = $this->limparAspas($regime_usuario);
        $usuario->situacao_usuario = $this->checkSituacaoFuncional($situacao_funcional_usuario);
        $usuario->data_atualizacao_usuario = $data_insercao_atualizacao;
        $usuario->save();

        return true;
    }

    private function insertUsuario($id_orgao, $id_lotacao, $id_usuario, $cpf_usuario, $nome_usuario, $cargo_usuario, $data_admissao_usuario, $regime_usuario, $situacao_funcional_usuario)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        Usuario::query()->create([
            'id_orgao_exercicio_usuario' => $id_orgao,
            'id_lotacao_exercicio_usuario' => $id_lotacao,
            'id_usuario' => $id_usuario,
            'id_tipo_usuario' => $this->checkTipoUsuario($regime_usuario),
            'cpf_usuario' => $this->limparAspas($cpf_usuario),
            'nome_usuario' => $this->limparAspas($nome_usuario),
            'cargo_usuario' => $this->limparAspas($cargo_usuario),
            'matricula_usuario' => $id_usuario,
            'data_admissao_usuario' => $this->limparAspas($data_admissao_usuario),
            'regime_usuario' => $this->limparAspas($regime_usuario),
            'situacao_usuario' => $this->checkSituacaoFuncional($situacao_funcional_usuario),
            'data_criacao_usuario' => $data_insercao_atualizacao,
            'data_atualizacao_usuario' => $data_insercao_atualizacao,
        ]);

        return true;
    }

    private function desativaUsuarios($idOrgao)
    {
    }

    private function checkSituacaoFuncional($situacao)
    {
        if ($this->limparAspas($situacao) === 'Trabalhando') {
            return 'A';
        } else if ($this->limparAspas($situacao) === 'Afastado') {
            return 'F';
        } else if ($this->limparAspas($situacao) === 'Demitido') {
            return 'D';
        } else if ($this->limparAspas($situacao) === 'Aposentado') {
            return 'P';
        }
    }

    private function checkTipoUsuario($tipo)
    {
        if ($this->limparAspas($tipo) === 'Efetivo Estatutário' || $this->limparAspas($tipo) === 'Celetista') {
            return 1;
        } else if ($this->limparAspas($tipo) === 'Comissionado' || $this->limparAspas($tipo) === 'Agente Político') {
            return 2;
        } else if ($this->limparAspas($tipo) === 'Estagiário') {
            return 3;
        }
    }

    private function getFerias($codOrgao)
    {
        $fileDir = $this->makeFile('ferias');

        $stSql = "SELECT  i_entidades as orgao, i_ferias cod_ferias, i_funcionarios matricula, dt_gozo_ini inicio_ferias, dt_gozo_fin fim_ferias, dt_gozo_fin-dt_gozo_ini qtd_dias_ferias from bethadba.ferias" .
            "" . ($codOrgao !== 0 ? " WHERE i_entidades = $codOrgao" : "") . ";" .
            "OUTPUT TO " . $fileDir . ";";

        $this->makeCommand($stSql);

        return $this->decodeFile($fileDir);
    }

    private function verificarFerias($matricula_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
    {
        $ferias = Ferias::query()->where('matricula_ferias', $matricula_ferias)
            ->where('data_inicio_ferias', $this->limparAspas($data_inicio_ferias))
            ->where('data_fim_ferias', $this->limparAspas($data_fim_ferias))
            ->where('qtd_dias_ferias', $qtd_dias_ferias)
            ->get(['id_ferias']);

        if ($ferias->count()) {
            return $this->updateFerias($ferias->first()->id_ferias, $matricula_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias);
        }

        return $this->insertFerias($matricula_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias);
    }

    private function insertFerias($matricula_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $ferias = Ferias::query()->create([
            'matricula_ferias' => $matricula_ferias,
            'data_inicio_ferias' => $this->limparAspas($data_inicio_ferias),
            'data_fim_ferias' => $this->limparAspas($data_fim_ferias),
            'qtd_dias_ferias' => $qtd_dias_ferias,
            'data_criacao_ferias' => $data_insercao_atualizacao,
            'data_atualizacao_ferias' => $data_insercao_atualizacao,
        ]);

        return $ferias->id_ferias;
    }

    private function updateFerias($feriasId, $matricula_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $ferias = Ferias::query()->where('id_ferias', $feriasId)->first();

        $ferias->matricula_ferias = $matricula_ferias;
        $ferias->data_inicio_ferias = $this->limparAspas($data_inicio_ferias);
        $ferias->data_fim_ferias = $this->limparAspas($data_fim_ferias);
        $ferias->qtd_dias_ferias = $qtd_dias_ferias;
        $ferias->data_atualizacao_ferias = $data_insercao_atualizacao;
        $ferias->save();

        return $ferias->id_ferias;
    }

    private function removeFerias($codFeriasData, $codOrgao)
    {
        if ($codFeriasData) {

            Ferias::query()->leftJoin('usuario', 'ferias.matricula_ferias', 'usuario.id_usuario')->whereNotIn('ferias.id_ferias', $codFeriasData)
                ->where('usuario.id_orgao_exercicio_usuario', $codOrgao)->delete();
        }
    }

    private function getAfastamentos($codOrgao)
    {
        $fileDir = $this->makeFile('afastamentos');

        $stSql = "SELECT i_entidades AS orgao, i_funcionarios matricula, descricao descricao_licenca, (SELECT count(*) FROM bethadba.afastamentos a2 WHERE a2.i_funcionarios = a.i_funcionarios AND a2.dt_afastamento <= a.dt_afastamento) AS cod_licenca, " .
            "dt_afastamento inicio_licenca, dt_ultimo_dia fim_licenca, IF dt_ultimo_dia IS NOT NULL THEN dt_ultimo_dia - dt_afastamento ENDIF AS qtd_dias_licenca " .
            "FROM bethadba.afastamentos a KEY JOIN bethadba.tipos_afast WHERE a.i_entidades = " . $codOrgao . " ORDER BY 1, 3; " .
            "OUTPUT TO " . $fileDir . ";";

        $this->makeCommand($stSql);

        return $this->decodeFile($fileDir);
    }

    private function verificarAfastamentos($codOrgao, $matricula, $descricao, $inicio, $fim, $qtdDias)
    {

        $afastamentos = Afastamento::query()
            ->where('id_orgao', $codOrgao)
            ->where('matricula_afastamento', $matricula)
            ->where('descricao_afastamento', $this->limparAspas($descricao))
            ->where('data_inicio_afastamento', $this->limparAspas($inicio))
            ->where('data_fim_afastamento', $this->limparAspas($fim))
            ->where('qtd_dias_afastamento', $qtdDias)
            ->get(['id_afastamento']);

        if ($afastamentos->count()) {
            return $this->updateAfastamentos($afastamentos->first()->id_afastamento, $codOrgao, $matricula, $descricao, $inicio, $fim, $qtdDias);
        }

        return $this->insertAfastamentos($codOrgao, $matricula, $descricao, $inicio, $fim, $qtdDias);
    }

    private function insertAfastamentos($codOrgao, $matricula, $descricao, $inicio, $fim, $qtdDias)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $afastamento = Afastamento::query()->create([
            'id_orgao' => $codOrgao,
            'matricula_afastamento' => $matricula,
            'descricao_afastamento' => $this->limparAspas($descricao),
            'data_inicio_afastamento' => $this->limparAspas($inicio) === '' ? null : $this->limparAspas($inicio),
            'data_fim_afastamento' => $this->limparAspas($fim) === '' ? null : $this->limparAspas($fim),
            'qtd_dias_afastamento' => $qtdDias === '' ? null : $qtdDias,
            'data_criacao_afastamento' => $data_insercao_atualizacao,
            'data_atualizacao_afastamento' => $data_insercao_atualizacao,
        ]);

        return $afastamento->id_afastamento;
    }

    private function updateAfastamentos($afastamentoId, $codOrgao, $matricula, $descricao, $inicio, $fim, $qtdDias)
    {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $afastamento = Afastamento::query()->where('id_afastamento', $afastamentoId)->first();

        $afastamento->id_orgao = $codOrgao;
        $afastamento->matricula_afastamento = $matricula;
        $afastamento->descricao_afastamento = $this->limparAspas($descricao);
        $afastamento->data_inicio_afastamento = $this->limparAspas($inicio) === '' ? null : $this->limparAspas($inicio);
        $afastamento->data_fim_afastamento = $this->limparAspas($fim) === '' ? null : $this->limparAspas($fim);
        $afastamento->qtd_dias_afastamento = $qtdDias === '' ? null : $qtdDias;
        $afastamento->data_atualizacao_afastamento = $data_insercao_atualizacao;
        $afastamento->save();

        return $afastamento->id_afastamento;
    }

    private function removeAfastamentos($codAfastamentos, $codOrgao)
    {
        if ($codAfastamentos) {

            Afastamento::query()->leftJoin('usuario', 'afastamentos.matricula_afastamento', 'usuario.matricula_usuario')->whereNotIn('afastamentos.id_afastamento', $codAfastamentos)
                ->where('usuario.id_orgao_exercicio_usuario', $codOrgao)->delete();
        }
    }

    public function makeCommand(string $sqlCommand)
    {
        $stComando = "\"%ASANY9%/win32/dbisql.exe\" -nogui -datasource " . $this->dsn . " -codepage utf8 -q \"" . $sqlCommand . "\" ";

        //exec("$stComando 2>&1", $output, $return_var);
        exec($stComando);

        //var_dump($output);
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

        $this->removeFile($fileDir);

        return $csv;
    }

    private function makeFile(string $fileName): string
    {
        $path = FileSystem::makeStorageDir('generated', true);
        $definedFileName = $fileName . ".csv";
        $fileDir = $path . DIRECTORY_SEPARATOR . $definedFileName;

        return $fileDir;
    }

    private function limparAspas($item)
    {
        return str_replace("'", "", $item);
    }
}
