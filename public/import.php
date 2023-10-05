<?php
include_once(__DIR__ . '/../config/database.php');
date_default_timezone_set("America/Rio_Branco");
error_reporting(E_ALL & ~E_NOTICE);

define('DATA_INSERCAO_ATUALIZACAO', Date('Y-m-d H:i:s'));

class ConectionTURMALINA
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            try {
                self::$instance = oci_connect(DATABASE_TURMALINA['username'], DATABASE_TURMALINA['password'], '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ' . DATABASE_TURMALINA['host'] . ' )(PORT = ' . DATABASE_TURMALINA['port'] . ' ))(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = ' . DATABASE_TURMALINA['service'] . ' )))', DATABASE_TURMALINA['mode']);
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
        return self::$instance;
    }
}

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

function migracao($cod_orgao = false)
{
    try {
        if (verificarImportacao()) {
            iniciarImportacao();

            $orgaos = getOrgaos($cod_orgao);

            foreach ($orgaos['data'] as $orgao) {
                verificarOrgao(
                    $orgao['COD_ORGAO'],
                    $orgao['NOME_ORGAO'],
                    $orgao['SIGLA_ORGAO']
                );

                $lotacoes = getLotacoes($orgao['COD_ORGAO']);
                $COD_LOTACOES = [];

                foreach ($lotacoes['data'] as $lotacao) {
                    $COD_LOTACOES[] = $lotacao['COD_LOTACAO'];

                    verificarLotacao(
                        $lotacao['COD_LOTACAO'],
                        $lotacao['COD_ORGAO'],
                        $lotacao['LOTACAO'],
                        $lotacao['SIGLA'],
                        $lotacao['MUNICIPIO']
                    );
                }

                removeLotacoes($COD_LOTACOES, $orgao['COD_ORGAO']);


                $usuarios = getUsuarios($orgao['COD_ORGAO']);

                foreach ($usuarios['data'] as $usuario) {
                    verificarUsuario(
                        $usuario['COD_ORGAO_EXERCICIO'],
                        $usuario['COD_LOTACAO_EXERCICIO'],
                        $usuario['MATRICULA'],
                        $usuario['CPF'],
                        $usuario['CONTRATO'],
                        $usuario['TIPO_CONTRATO'],
                        $usuario['NOME'],
                        $usuario['SITUACAO_FUNCIONAL'],
                        $usuario['DTA_ADMISSAO'],
                        $usuario['CARGO_CARREIRA'],
                        $usuario['CARGO_COMISSAO'],
                        $usuario['EMAIL_FUNCIONAL'],
                        $usuario['REGIME']
                    );
                }

                $ferias_ = getFerias($orgao['COD_ORGAO']);
                $COD_FERIAS = [];
                foreach ($ferias_['data'] as $ferias) {
                    $COD_FERIAS[] = verificarFerias(
                        $ferias['COD_FERIAS'],
                        $ferias['MATRICULA'],
                        $ferias['CONTRATO'],
                        $ferias['INICIO_FERIAS'],
                        $ferias['FIM_FERIAS'],
                        $ferias['QTD_DIAS_FERIAS']
                    );
                }

                removerFerias($COD_FERIAS, $orgao['COD_ORGAO']);

                $afastamentos_ = getAfastamentos($orgao['COD_ORGAO']);
                $COD_AFASTAMENTO = [];
                foreach ($afastamentos_['data'] as $ferias) {
                    $COD_AFASTAMENTO[] = verificarAfastamento(
                        $ferias['COD_LICENCA'],
                        $ferias['MATRICULA'],
                        $ferias['CONTRATO'],
                        $ferias['DESCRICAO_LICENCA'],
                        $ferias['INICIO_LICENCA'],
                        $ferias['FIM_LICENCA'],
                        $ferias['QTD_DIAS_LICENCA']
                    );
                }
                removerAfastamento($COD_AFASTAMENTO, $orgao['COD_ORGAO']);

                desativarUsuario($orgao['COD_ORGAO']);
            }

            verificarFaltas();

            verificarHierarquia();

            verificarAfastamentoTemporarios();

            pararImportacao();
        } else {
            print 'Existe uma importação em progresso!';
        }
    } catch (\Throwable $th) {
        iniciarErroImportacao($th->getMessage() . ' - Line: ' .  $th->getLine());
        print $th->getMessage() . ' - Line: ' .  $th->getLine();
    }
}

function getOrgaos($cod_orgao = false)
{
    $query = '';
    if ($cod_orgao) {
        $query = " AND COD_ORGAO = $cod_orgao ";
    }

    $sql = "SELECT COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO FROM VW_LOTACAO WHERE NOT (COD_ORGAO IN (1,61,70,91)) $query GROUP BY COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO";
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    return ['data' => $data, 'rowCount' => $rowCount];
}

function getLotacoes($cod_orgao)
{
    $sql = "SELECT 
            COD_LOTACAO,
            LOTACAO,
            SIGLA,
            MUNICIPIO,
            COD_ORGAO
        FROM VW_LOTACAO WHERE COD_ORGAO = " . $cod_orgao;
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    return ['data' => $data, 'rowCount' => $rowCount];
}

function getUsuarios($cod_orgao)
{
    $sql = "SELECT 
            MATRICULA,
            CONTRATO,
            CPF,
            NOME,
			to_char( DTA_ADMISSAO, 'yyyy-mm-dd') DTA_ADMISSAO,
            CARGO_CARREIRA,
            CARGO_COMISSAO,
            COD_ORGAO_EXERCICIO,
            COD_LOTACAO_EXERCICIO,
            EMAIL_FUNCIONAL,
            TIPO_CONTRATO,
            SITUACAO_FUNCIONAL, 
			REGIME
        FROM VW_SERVIDOR WHERE COD_ORGAO_EXERCICIO = " . $cod_orgao;
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    return ['data' => $data, 'rowCount' => $rowCount];
}

function getFerias($cod_orgao)
{
    $sql = "SELECT 
        f.COD_FERIAS,
        f.MATRICULA, 
        f.CONTRATO,
        to_char( f.INICIO_FERIAS, 'yyyy-mm-dd') INICIO_FERIAS,
        to_char( f.FIM_FERIAS, 'yyyy-mm-dd') FIM_FERIAS,
        f.QTD_DIAS_FERIAS
            FROM VW_FERIAS f 
            INNER JOIN VW_SERVIDOR s ON f.MATRICULA = s.MATRICULA 
            WHERE EXTRACT(year FROM f.fim_ferias) >= 2019 AND s.COD_ORGAO_EXERCICIO = " . $cod_orgao;
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    return ['data' => $data, 'rowCount' => $rowCount];
}

function getAfastamentos($cod_orgao)
{
    $alter = "ALTER SESSION SET NLS_DATE_FORMAT='DD-MM-YYYY'";
    $exec = oci_parse(ConectionTURMALINA::getInstance(), $alter);
    oci_execute($exec);

    $sql = "SELECT 
			a.MATRICULA,
			a.CONTRATO,
			a.DESCRICAO_LICENCA,
			a.COD_LICENCA,
			to_char( a.INICIO_LICENCA, 'yyyy-mm-dd') INICIO_LICENCA,
			to_char( a.FIM_LICENCA, 'yyyy-mm-dd') FIM_LICENCA,
			a.QTD_DIAS_LICENCA
		FROM TURMALINA.vw_afastamentos a 
		INNER JOIN VW_SERVIDOR s ON a.MATRICULA = s.MATRICULA AND a.CONTRATO = s.CONTRATO
		WHERE EXTRACT(year FROM a.FIM_LICENCA) >= EXTRACT(year FROM current_date) AND s.COD_ORGAO_EXERCICIO = " . $cod_orgao;
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    return ['data' => $data, 'rowCount' => $rowCount];
}

function verificarImportacao()
{
    $sql = "SELECT * FROM importacao WHERE situacao_importacao = 'S'";
    $stm = Conection::prepare($sql);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count > 0) {
        return false;
    }

    return true;
}

function iniciarImportacao()
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
    $situacao_importacao = 'S';

    $sql = "INSERT INTO importacao ( situacao_importacao, data_criacao_importacao, data_atualizacao_importacao ) VALUES ( :situacao_importacao, :data_criacao_importacao, :data_atualizacao_importacao )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':situacao_importacao', $situacao_importacao, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_importacao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_importacao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function pararImportacao()
{
    $sql = "UPDATE importacao SET situacao_importacao = 'N', data_atualizacao_importacao = :data_atualizacao_importacao WHERE situacao_importacao = 'S'";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':data_atualizacao_importacao', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function iniciarErroImportacao($erro)
{
    $sql = "INSERT INTO erro_importacao ( descricao_erro_importacao, data_criacao_erro_importacao, data_atualizacao_erro_importacao ) VALUES ( :descricao_erro_importacao, :data_criacao_erro_importacao, :data_atualizacao_erro_importacao )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':descricao_erro_importacao', $erro, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_erro_importacao', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_erro_importacao', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stm->execute();

    pararImportacao();

    return true;
}

// <-- START ORGÃO -->

function verificarOrgao($id_orgao, $descricao_orgao, $sigla_orgao)
{
    $sql = "SELECT id_orgao FROM orgao WHERE id_orgao = :id_orgao";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_orgao', $id_orgao, PDO::PARAM_INT);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count) {
        updateOrgao($id_orgao, $descricao_orgao, $sigla_orgao);
    } else {
        insertOrgao($id_orgao, $descricao_orgao, $sigla_orgao);
    }

    return true;
}

function insertOrgao($id_orgao, $descricao_orgao, $sigla_orgao)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "INSERT INTO orgao ( id_orgao, descricao_orgao, sigla_orgao, data_criacao_orgao, data_atualizacao_orgao ) VALUES ( :id_orgao, :descricao_orgao, :sigla_orgao, :data_criacao_orgao, :data_atualizacao_orgao )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_orgao', $id_orgao, PDO::PARAM_INT);
    $stm->bindParam(':descricao_orgao', $descricao_orgao, PDO::PARAM_STR);
    $stm->bindParam(':sigla_orgao', $sigla_orgao, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_orgao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_orgao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function updateOrgao($id_orgao, $descricao_orgao, $sigla_orgao)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "UPDATE orgao SET descricao_orgao = :descricao_orgao, sigla_orgao = :sigla_orgao, data_atualizacao_orgao = :data_atualizacao_orgao WHERE id_orgao = :id_orgao";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_orgao', $id_orgao, PDO::PARAM_INT);
    $stm->bindParam(':descricao_orgao', $descricao_orgao, PDO::PARAM_STR);
    $stm->bindParam(':sigla_orgao', $sigla_orgao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_orgao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

// <-- END ORGÃO -->

// <-- START LOTAÇÃO -->

function verificarLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $sigla_lotacao, $municipio_lotacao)
{
    $sql = "SELECT id_lotacao FROM lotacao WHERE id_lotacao = :id_lotacao";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_lotacao', $id_lotacao, PDO::PARAM_INT);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count) {
        updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $sigla_lotacao, $municipio_lotacao);
    } else {
        insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $sigla_lotacao, $municipio_lotacao);
    }

    return true;
}

function insertLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $sigla_lotacao, $municipio_lotacao)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "INSERT INTO lotacao ( id_lotacao, id_orgao, id_tipo_lotacao, descricao_lotacao, sigla_lotacao, municipio_lotacao, data_criacao_lotacao, data_atualizacao_lotacao ) VALUES ( :id_lotacao, :id_orgao, 0, :descricao_lotacao, :sigla_lotacao, :municipio_lotacao, :data_criacao_lotacao, :data_atualizacao_lotacao )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_lotacao', $id_lotacao, PDO::PARAM_INT);
    $stm->bindParam(':id_orgao', $id_orgao, PDO::PARAM_INT);
    $stm->bindParam(':descricao_lotacao', $descricao_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':sigla_lotacao', $sigla_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':municipio_lotacao', $municipio_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_lotacao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_lotacao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function updateLotacao($id_lotacao, $id_orgao, $descricao_lotacao, $sigla_lotacao, $municipio_lotacao)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "UPDATE lotacao SET status_lotacao = 'A', id_orgao = :id_orgao, descricao_lotacao = :descricao_lotacao, sigla_lotacao = :sigla_lotacao, municipio_lotacao = :municipio_lotacao, data_atualizacao_lotacao = :data_atualizacao_lotacao WHERE id_lotacao = :id_lotacao";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_lotacao', $id_lotacao, PDO::PARAM_INT);
    $stm->bindParam(':id_orgao', $id_orgao, PDO::PARAM_INT);
    $stm->bindParam(':descricao_lotacao', $descricao_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':sigla_lotacao', $sigla_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':municipio_lotacao', $municipio_lotacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_lotacao', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function removeLotacoes($COD_LOTACOES, $ID_ORGAO)
{
    if ($COD_LOTACOES) {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $sql = "UPDATE lotacao SET status_lotacao = 'R', data_atualizacao_lotacao = :data_atualizacao_lotacao WHERE id_lotacao NOT IN (" . implode(",", $COD_LOTACOES) . ") AND id_orgao=:id_orgao ";
        $stm = Conection::prepare($sql);
        $stm->bindParam(':id_orgao', $ID_ORGAO, PDO::PARAM_INT);
        $stm->bindParam(':data_atualizacao_lotacao', $data_insercao_atualizacao, PDO::PARAM_STR);
        $stm->execute();
    }

    return true;
}

// <-- END LOTAÇÃO -->

// <-- START USUÁRIO -->

function verificarUsuario(
    $id_orgao_exercicio_usuario,
    $id_lotacao_exercicio_usuario,
    $matricula_usuario,
    $cpf_usuario,
    $contrato_usuario,
    $tipo_contrato_usuario,
    $nome_usuario,
    $situacao_funcional_usuario,
    $data_admissao_usuario,
    $cargo_usuario,
    $cargo_comissao_usuario,
    $email_usuario,
    $regime_usuario
) {
    $sql = "SELECT matricula_usuario, contrato_usuario FROM usuario WHERE matricula_usuario = :matricula_usuario AND contrato_usuario = :contrato_usuario";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':matricula_usuario', $matricula_usuario, PDO::PARAM_INT);
    $stm->bindParam(':contrato_usuario', $contrato_usuario, PDO::PARAM_INT);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count) {
        updateUsuario(
            $id_orgao_exercicio_usuario,
            $id_lotacao_exercicio_usuario,
            $matricula_usuario,
            $cpf_usuario,
            $contrato_usuario,
            $tipo_contrato_usuario,
            $nome_usuario,
            $situacao_funcional_usuario,
            $data_admissao_usuario,
            $cargo_usuario,
            $cargo_comissao_usuario,
            $email_usuario,
            $regime_usuario
        );
    } else {
        insertUsuario(
            $id_orgao_exercicio_usuario,
            $id_lotacao_exercicio_usuario,
            $matricula_usuario,
            $cpf_usuario,
            $contrato_usuario,
            $tipo_contrato_usuario,
            $nome_usuario,
            $situacao_funcional_usuario,
            $data_admissao_usuario,
            $cargo_usuario,
            $cargo_comissao_usuario,
            $email_usuario,
            $regime_usuario
        );
    }

    return true;
}

function insertUsuario(
    $id_orgao_exercicio_usuario,
    $id_lotacao_exercicio_usuario,
    $matricula_usuario,
    $cpf_usuario,
    $contrato_usuario,
    $tipo_contrato_usuario,
    $nome_usuario,
    $situacao_funcional_usuario,
    $data_admissao_usuario,
    $cargo_usuario,
    $cargo_comissao_usuario,
    $email_usuario,
    $regime_usuario
) {
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
    $id_tipo_usuario = 1;

    $sql = "INSERT INTO usuario ( 
			id_tipo_usuario,
			id_orgao_exercicio_usuario, 
			id_lotacao_exercicio_usuario, 
			matricula_usuario, 
			cpf_usuario, 
			contrato_usuario,
			tipo_contrato_usuario,
			nome_usuario,
			situacao_funcional_usuario,
			data_admissao_usuario,
			cargo_usuario,
			cargo_comissao_usuario,
			email_usuario,
			regime_usuario,
			data_criacao_usuario,
			data_atualizacao_usuario
		) VALUES ( 
			:id_tipo_usuario,
			:id_orgao_exercicio_usuario, 
			:id_lotacao_exercicio_usuario, 
			:matricula_usuario, 
			:cpf_usuario, 
			:contrato_usuario,
			:tipo_contrato_usuario,
			:nome_usuario,
			:situacao_funcional_usuario,
			:data_admissao_usuario,
			:cargo_usuario,
			:cargo_comissao_usuario,
			:email_usuario,
			:regime_usuario,
			:data_criacao_usuario,
			:data_atualizacao_usuario
		)";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_tipo_usuario', $id_tipo_usuario, PDO::PARAM_INT);
    $stm->bindParam(':id_orgao_exercicio_usuario', $id_orgao_exercicio_usuario, PDO::PARAM_INT);
    $stm->bindParam(':id_lotacao_exercicio_usuario', $id_lotacao_exercicio_usuario, PDO::PARAM_INT);
    $stm->bindParam(':matricula_usuario', $matricula_usuario, PDO::PARAM_INT);
    $stm->bindParam(':cpf_usuario', $cpf_usuario, PDO::PARAM_STR);
    $stm->bindParam(':contrato_usuario', $contrato_usuario, PDO::PARAM_INT);
    $stm->bindParam(':tipo_contrato_usuario', $tipo_contrato_usuario, PDO::PARAM_STR);
    $stm->bindParam(':nome_usuario', $nome_usuario, PDO::PARAM_STR);
    $stm->bindParam(':situacao_funcional_usuario', $situacao_funcional_usuario, PDO::PARAM_STR);
    $stm->bindParam(':data_admissao_usuario', $data_admissao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':cargo_usuario', $cargo_usuario, PDO::PARAM_STR);
    $stm->bindParam(':cargo_comissao_usuario', $cargo_comissao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':email_usuario', $email_usuario, PDO::PARAM_STR);
    $stm->bindParam(':regime_usuario', $regime_usuario, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_usuario', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_usuario', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function updateUsuario(
    $id_orgao_exercicio_usuario,
    $id_lotacao_exercicio_usuario,
    $matricula_usuario,
    $cpf_usuario,
    $contrato_usuario,
    $tipo_contrato_usuario,
    $nome_usuario,
    $situacao_funcional_usuario,
    $data_admissao_usuario,
    $cargo_usuario,
    $cargo_comissao_usuario,
    $email_usuario,
    $regime_usuario
) {
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
    $situacao_usuario = 'A';

    $sql = "UPDATE usuario SET 
			id_orgao_exercicio_usuario = :id_orgao_exercicio_usuario, 
			id_lotacao_exercicio_usuario = :id_lotacao_exercicio_usuario, 
			cpf_usuario = :cpf_usuario, 
			tipo_contrato_usuario = :tipo_contrato_usuario,
			nome_usuario = :nome_usuario,
			situacao_funcional_usuario = :situacao_funcional_usuario,
			data_admissao_usuario = :data_admissao_usuario,
			cargo_usuario = :cargo_usuario,
			cargo_comissao_usuario = :cargo_comissao_usuario,
			email_usuario = :email_usuario,
			regime_usuario = :regime_usuario,
			situacao_usuario = :situacao_usuario,
			data_atualizacao_usuario = :data_atualizacao_usuario

			WHERE matricula_usuario = :matricula_usuario AND contrato_usuario = :contrato_usuario";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_orgao_exercicio_usuario', $id_orgao_exercicio_usuario, PDO::PARAM_INT);
    $stm->bindParam(':id_lotacao_exercicio_usuario', $id_lotacao_exercicio_usuario, PDO::PARAM_INT);
    $stm->bindParam(':matricula_usuario', $matricula_usuario, PDO::PARAM_INT);
    $stm->bindParam(':cpf_usuario', $cpf_usuario, PDO::PARAM_STR);
    $stm->bindParam(':contrato_usuario', $contrato_usuario, PDO::PARAM_INT);
    $stm->bindParam(':tipo_contrato_usuario', $tipo_contrato_usuario, PDO::PARAM_STR);
    $stm->bindParam(':nome_usuario', $nome_usuario, PDO::PARAM_STR);
    $stm->bindParam(':situacao_funcional_usuario', $situacao_funcional_usuario, PDO::PARAM_STR);
    $stm->bindParam(':data_admissao_usuario', $data_admissao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':cargo_usuario', $cargo_usuario, PDO::PARAM_STR);
    $stm->bindParam(':cargo_comissao_usuario', $cargo_comissao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':email_usuario', $email_usuario, PDO::PARAM_STR);
    $stm->bindParam(':regime_usuario', $regime_usuario, PDO::PARAM_STR);
    $stm->bindParam(':situacao_usuario', $situacao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_usuario', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function desativarUsuario($id_orgao_exercicio_usuario)
{
    $data_insercao_atualizacao = date('Y-m-d', strtotime(DATA_INSERCAO_ATUALIZACAO));

    $situacao_usuario = 'D';

    $sql = "UPDATE usuario SET situacao_usuario = :situacao_usuario
                WHERE (date(data_atualizacao_usuario) < :data_atualizacao_usuario) AND id_tipo_usuario = 1 AND id_orgao_exercicio_usuario = :id_orgao_exercicio_usuario";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':situacao_usuario', $situacao_usuario, PDO::PARAM_STR);
    $stm->bindParam(':id_orgao_exercicio_usuario', $id_orgao_exercicio_usuario, PDO::PARAM_INT);
    $stm->bindParam(':data_atualizacao_usuario', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

// <-- END USUÁRIO -->

// <-- START FERIAS -->

function verificarFerias($id_ferias_turmalina, $matricula_ferias, $contrato_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
{
    $sql = "SELECT id_ferias FROM ferias WHERE id_ferias_turmalina = :id_ferias_turmalina  AND matricula_ferias = :matricula_ferias AND contrato_ferias = :contrato_ferias";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_ferias_turmalina', $id_ferias_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_ferias', $matricula_ferias, PDO::PARAM_INT);
    $stm->bindParam(':contrato_ferias', $contrato_ferias, PDO::PARAM_INT);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count) {
        $retorno = $stm->fetchAll(PDO::FETCH_OBJ)[0]->id_ferias;
        updateFerias($id_ferias_turmalina, $matricula_ferias, $contrato_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias);
    } else {
        $retorno = insertFerias($id_ferias_turmalina, $matricula_ferias, $contrato_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias);
    }

    return $retorno;
}

function insertFerias($id_ferias_turmalina, $matricula_ferias, $contrato_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "INSERT 
			INTO ferias ( id_ferias_turmalina, matricula_ferias, contrato_ferias, data_inicio_ferias, data_fim_ferias, qtd_dias_ferias, data_criacao_ferias, data_atualizacao_ferias ) 
			VALUES ( :id_ferias_turmalina, :matricula_ferias, :contrato_ferias, :data_inicio_ferias, :data_fim_ferias, :qtd_dias_ferias, :data_criacao_ferias, :data_atualizacao_ferias )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_ferias_turmalina', $id_ferias_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_ferias', $matricula_ferias, PDO::PARAM_INT);
    $stm->bindParam(':contrato_ferias', $contrato_ferias, PDO::PARAM_INT);
    $stm->bindParam(':data_inicio_ferias', $data_inicio_ferias, PDO::PARAM_STR);
    $stm->bindParam(':data_fim_ferias', $data_fim_ferias, PDO::PARAM_STR);
    $stm->bindParam(':qtd_dias_ferias', $qtd_dias_ferias, PDO::PARAM_STR);
    $stm->bindParam(':data_criacao_ferias', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_ferias', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return Conection::getInstance()->lastInsertId();
}

function updateFerias($id_ferias_turmalina, $matricula_ferias, $contrato_ferias, $data_inicio_ferias, $data_fim_ferias, $qtd_dias_ferias)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "UPDATE ferias SET
			data_inicio_ferias = :data_inicio_ferias, 
			data_fim_ferias = :data_fim_ferias,
			qtd_dias_ferias = :qtd_dias_ferias,
			data_atualizacao_ferias = :data_atualizacao_ferias

			WHERE id_ferias_turmalina = :id_ferias_turmalina AND matricula_ferias = :matricula_ferias AND contrato_ferias = :contrato_ferias";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_ferias_turmalina', $id_ferias_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_ferias', $matricula_ferias, PDO::PARAM_INT);
    $stm->bindParam(':contrato_ferias', $contrato_ferias, PDO::PARAM_INT);
    $stm->bindParam(':data_inicio_ferias', $data_inicio_ferias, PDO::PARAM_STR);
    $stm->bindParam(':data_fim_ferias', $data_fim_ferias, PDO::PARAM_STR);
    $stm->bindParam(':qtd_dias_ferias', $qtd_dias_ferias, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_ferias', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function removerFerias($COD_FERIAS, $ID_ORGAO)
{
    if ($COD_FERIAS) {
        $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

        $sql = "DELETE f.* FROM ferias f LEFT JOIN usuario u ON u.matricula_usuario=f.matricula_ferias WHERE f.id_ferias NOT IN (" . implode(",", $COD_FERIAS) . ") AND u.id_orgao_exercicio_usuario='$ID_ORGAO'";
        $stm = Conection::prepare($sql);
        $stm->execute();
    }

    return true;
}

// <-- END FERIAS -->

// <-- START AFASTAMENTOS -->

function verificarAfastamento($id_licenca_turmalina, $matricula_afastamento, $contrato_afastamento, $descricao_afastamento, $data_inicio_afastamento, $data_fim_afastamento, $qtd_dias_afastamento)
{
    $sql = "SELECT id_afastamento 
			FROM afastamento 
			WHERE id_licenca_turmalina = :id_licenca_turmalina  AND 
				matricula_afastamento = :matricula_afastamento AND 
				contrato_afastamento = :contrato_afastamento AND
				data_inicio_afastamento = :data_inicio_afastamento";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_licenca_turmalina', $id_licenca_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_afastamento', $matricula_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':contrato_afastamento', $contrato_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':data_inicio_afastamento', $data_inicio_afastamento, PDO::PARAM_STR);
    $stm->execute();
    $count = $stm->rowCount();

    if ($count) {
        $retorno = $stm->fetchAll(PDO::FETCH_OBJ)[0]->id_afastamento;

        updateAfastamento($id_licenca_turmalina, $matricula_afastamento, $contrato_afastamento, $descricao_afastamento, $data_inicio_afastamento, $data_fim_afastamento, $qtd_dias_afastamento);
    } else {
        $retorno = insertAfastamento($id_licenca_turmalina, $matricula_afastamento, $contrato_afastamento, $descricao_afastamento, $data_inicio_afastamento, $data_fim_afastamento, $qtd_dias_afastamento);
    }

    return $retorno;
}

function insertAfastamento($id_licenca_turmalina, $matricula_afastamento, $contrato_afastamento, $descricao_afastamento, $data_inicio_afastamento, $data_fim_afastamento, $qtd_dias_afastamento)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "INSERT 
			INTO afastamento ( id_licenca_turmalina, matricula_afastamento, contrato_afastamento, descricao_afastamento, data_inicio_afastamento, data_fim_afastamento, qtd_dias_afastamento, data_criacao_afastamento, data_atualizacao_afastamento ) 
			VALUES ( :id_licenca_turmalina, :matricula_afastamento, :contrato_afastamento, :descricao_afastamento, :data_inicio_afastamento, :data_fim_afastamento, :qtd_dias_afastamento, :data_criacao_afastamento, :data_atualizacao_afastamento )";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_licenca_turmalina', $id_licenca_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_afastamento', $matricula_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':contrato_afastamento', $contrato_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':descricao_afastamento', $descricao_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':data_inicio_afastamento', $data_inicio_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':data_fim_afastamento', $data_fim_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':qtd_dias_afastamento', $qtd_dias_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':data_criacao_afastamento', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->bindParam(':data_atualizacao_afastamento', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return Conection::getInstance()->lastInsertId();;
}

function updateAfastamento($id_licenca_turmalina, $matricula_afastamento, $contrato_afastamento, $descricao_afastamento, $data_inicio_afastamento, $data_fim_afastamento, $qtd_dias_afastamento)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;

    $sql = "UPDATE afastamento SET
			descricao_afastamento = :descricao_afastamento,
			data_fim_afastamento = :data_fim_afastamento,
			qtd_dias_afastamento = :qtd_dias_afastamento,
			data_atualizacao_afastamento = :data_atualizacao_afastamento

			WHERE id_licenca_turmalina = :id_licenca_turmalina AND 
				matricula_afastamento = :matricula_afastamento AND 
				contrato_afastamento = :contrato_afastamento AND
				data_inicio_afastamento = :data_inicio_afastamento";
    $stm = Conection::prepare($sql);
    $stm->bindParam(':id_licenca_turmalina', $id_licenca_turmalina, PDO::PARAM_INT);
    $stm->bindParam(':matricula_afastamento', $matricula_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':contrato_afastamento', $contrato_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':descricao_afastamento', $descricao_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':data_inicio_afastamento', $data_inicio_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':data_fim_afastamento', $data_fim_afastamento, PDO::PARAM_STR);
    $stm->bindParam(':qtd_dias_afastamento', $qtd_dias_afastamento, PDO::PARAM_INT);
    $stm->bindParam(':data_atualizacao_afastamento', $data_insercao_atualizacao, PDO::PARAM_STR);
    $stm->execute();

    return true;
}

function removerAfastamento($COD_AFASTAMENTO, $ID_ORGAO)
{
    $data_insercao_atualizacao = DATA_INSERCAO_ATUALIZACAO;
    try {
        $sql = "DELETE a.* FROM afastamento a LEFT JOIN usuario u ON u.matricula_usuario=a.matricula_afastamento WHERE a.id_afastamento NOT IN (" . implode(",", $COD_AFASTAMENTO) . ") AND u.id_orgao_exercicio_usuario='$ID_ORGAO'";
        $stm = Conection::prepare($sql);
        $stm->execute();
    } catch (Exception $E) {
    }

    return true;
}
// <-- END AFASTAMENTOS -->

// <-- START HIERARQUIA -->

function verificarHierarquia()
{
    $sql = "SELECT * FROM vw_lotacao_hierarquia";
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    if ($data) {
        $limpar = "TRUNCATE TABLE hierarquia";
        $stm = Conection::prepare($limpar);
        $stm->execute();
    }

    foreach ($data as $dados) {
        $inserir = "INSERT 
			INTO hierarquia ( nivel_pai, id_lotacao, id_lotacao_subordinada ) 
			VALUES ( :nivel_pai, :id_lotacao, :id_lotacao_subordinada )";
        $stm = Conection::prepare($inserir);
        $stm->bindParam(':id_lotacao', $dados['COD_LOTACAO'], PDO::PARAM_INT);
        $stm->bindParam(':nivel_pai', $dados['COD_GRAU_HIERARQUIA'], PDO::PARAM_INT);
        $stm->bindParam(':id_lotacao_subordinada', $dados['COD_LOTACAO_SUBORDINADA'], PDO::PARAM_INT);
        $stm->execute();
    }

    $atualiza = "UPDATE lotacao l LEFT JOIN hierarquia h ON h.id_lotacao_subordinada=l.id_lotacao SET l.id_tipo_lotacao=h.nivel_pai+1 WHERE l.id_lotacao=h.id_lotacao_subordinada AND h.nivel_pai!=1";
    $stm = Conection::prepare($atualiza);
    $stm->execute();

    // $atualiza = "UPDATE lotacao l LEFT JOIN hierarquia h ON h.id_lotacao=l.id_lotacao SET l.id_tipo_lotacao=h.nivel_pai WHERE l.id_lotacao=h.id_lotacao AND h.nivel_pai=1";
    $atualiza = "UPDATE lotacao l LEFT JOIN hierarquia h ON h.id_lotacao=l.id_lotacao SET l.id_tipo_lotacao=h.nivel_pai WHERE l.id_lotacao=h.id_lotacao";
    $stm = Conection::prepare($atualiza);
    $stm->execute();

    return true;
}

// <-- END HIERARQUIA -->

// <-- START FALTAS -->

function verificarFaltas()
{
    $sql = "SELECT * FROM vw_faltas";
    $stid = oci_parse(ConectionTURMALINA::getInstance(), $sql);
    oci_execute($stid);
    $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

    if ($data) {
        $limpar = "TRUNCATE TABLE faltas";
        $stm = Conection::prepare($limpar);
        $stm->execute();
    }

    foreach ($data as $dados) {
        $inserir = "INSERT 
			INTO faltas ( tipo, qtd_dias, matricula_usuario, contrato, data_inicio, data_termino ) 
			VALUES ( :tipo, :qtd_dias, :matricula_usuario, :contrato, :data_inicio, :data_termino )";
        $stm = Conection::prepare($inserir);
        $stm->bindParam(':tipo', trim($dados['DESCRICAO_LANCAMENTO']), PDO::PARAM_STR);
        $stm->bindParam(':qtd_dias', intval($dados['QTD_DIAS']), PDO::PARAM_INT);
        $stm->bindParam(':matricula_usuario', intval($dados['MATRICULA']), PDO::PARAM_INT);
        $stm->bindParam(':contrato', intval($dados['CONTRATO']), PDO::PARAM_INT);
        $stm->bindParam(':data_inicio', date('Y-m-d', strtotime($dados['DATA_INICIO_FALTA'])), PDO::PARAM_STR);
        $stm->bindParam(':data_termino', date('Y-m-d', strtotime($dados['DATA_FIM_FALTA'])), PDO::PARAM_STR);
        $stm->execute();
    }

    return true;
}

// <-- END FALTAS -->

// <-- START TEMPORARIOS -->

function verificarAfastamentoTemporarios()
{
    $sql = "
			SELECT 
				* 
			FROM 
				afastamentos_temporario_lotacao_responsavel 
			WHERE 
				(
				data_inicial <= NOW() + INTERVAL 1 DAY
				AND status='A'
				) 
				or
				(
				data_final <= NOW() + INTERVAL 1 DAY
				AND status='E'
				)
			";
    $stm = Conection::prepare($sql);
    $stm->execute();
    $dados = $stm->fetchAll(PDO::FETCH_OBJ);

    foreach ($dados as $dado) {
        if ($dado->status == 'A') {
            /*//REMOVE O USUÁRIO
				$upd = "UPDATE lotacao_responsavel SET status_lotacao_responsavel='R', data_atualizacao_lotacao_responsavel=NOW(), id_usuario_atualizacao_lotacao_responsavel=:id_cadastrante WHERE id_lotacao=:id_lotacao AND id_usuario=:id_usuario AND status_lotacao_responsavel='A'";
				$stm = Conection::prepare($upd);
				$stm->bindParam(':id_lotacao', $dado->id_lotacao, PDO::PARAM_INT);
				$stm->bindParam(':id_cadastrante', $dado->id_usuario_cadastro, PDO::PARAM_INT);
				$stm->bindParam(':id_usuario', $dado->id_usuario, PDO::PARAM_INT);
				$stm->execute();*/

            //ATIVA O USUÁRIO TEMPORARIO
            $upd = "INSERT INTO lotacao_responsavel (id_lotacao, id_usuario, id_usuario_criacao_lotacao_responsavel, id_usuario_atualizacao_lotacao_responsavel, data_criacao_lotacao_responsavel, data_atualizacao_lotacao_responsavel, status_lotacao_responsavel) VALUES (:id_lotacao, :id_usuario, :id_usuario_criacao_lotacao_responsavel, :id_usuario_atualizacao_lotacao_responsavel, NOW(), NOW(), 'A')";
            $stm = Conection::prepare($upd);
            $stm->bindParam(':id_lotacao', $dado->id_lotacao, PDO::PARAM_INT);
            $stm->bindParam(':id_usuario', $dado->id_substituto, PDO::PARAM_INT);
            $stm->bindParam(':id_usuario_criacao_lotacao_responsavel', $dado->id_usuario_cadastro, PDO::PARAM_INT);
            $stm->bindParam(':id_usuario_atualizacao_lotacao_responsavel', $dado->id_usuario_cadastro, PDO::PARAM_INT);
            $stm->execute();

            //ALTERAR O STATUS DO AGENDAMENTO PARA EM ANDAMENTO
            $upd = "UPDATE afastamentos_temporario_lotacao_responsavel SET status='E', data_atualizacao=NOW() WHERE id_afastamento_temporario=:id_afastamento_temporario";
            $stm = Conection::prepare($upd);
            $stm->bindParam(':id_afastamento_temporario', $dado->id_afastamento_temporario, PDO::PARAM_INT);
            $stm->execute();
        }

        if ($dado->status == 'E') {
            //REMOVE O USUÁRIO TEMPORARIO
            $upd = "UPDATE lotacao_responsavel SET status_lotacao_responsavel='R', data_atualizacao_lotacao_responsavel=NOW(), id_usuario_atualizacao_lotacao_responsavel=:id_cadastrante WHERE id_lotacao=:id_lotacao AND id_usuario=:id_usuario AND status_lotacao_responsavel='A'";
            $stm = Conection::prepare($upd);
            $stm->bindParam(':id_lotacao', $dado->id_lotacao, PDO::PARAM_INT);
            $stm->bindParam(':id_cadastrante', $dado->id_usuario_cadastro, PDO::PARAM_INT);
            $stm->bindParam(':id_usuario', $dado->id_substituto, PDO::PARAM_INT);
            $stm->execute();

            //ATIVA O NOVO USUÁRIO
            /*$upd = "INSERT INTO lotacao_responsavel (id_lotacao, id_usuario, id_usuario_criacao_lotacao_responsavel, id_usuario_atualizacao_lotacao_responsavel, data_criacao_lotacao_responsavel, data_atualizacao_lotacao_responsavel, status_lotacao_responsavel) VALUES (:id_lotacao, :id_usuario, :id_usuario_criacao_lotacao_responsavel, :id_usuario_atualizacao_lotacao_responsavel, NOW(), NOW(), 'A')";
				$stm = Conection::prepare($upd);
				$stm->bindParam(':id_lotacao', $dado->id_lotacao, PDO::PARAM_INT);
				$stm->bindParam(':id_usuario', $dado->id_usuario, PDO::PARAM_INT);
				$stm->bindParam(':id_usuario_criacao_lotacao_responsavel', $dado->id_usuario_cadastro, PDO::PARAM_INT);
				$stm->bindParam(':id_usuario_atualizacao_lotacao_responsavel', $dado->id_usuario_cadastro, PDO::PARAM_INT);
				$stm->execute();*/

            //ALTERAR O STATUS DO AGENDAMENTO PARA EM ANDAMENTO
            $upd = "UPDATE afastamentos_temporario_lotacao_responsavel SET status='F', data_atualizacao=NOW() WHERE id_afastamento_temporario=:id_afastamento_temporario";
            $stm = Conection::prepare($upd);
            $stm->bindParam(':id_afastamento_temporario', $dado->id_afastamento_temporario, PDO::PARAM_INT);
            $stm->execute();
        }
    }


    return true;
}

// <-- END TEMPORARIOS -->

if ($argv[1] != null) {
    $dados = explode('=', $argv[1]);
    echo migracao($dados[1]);
} else {
    echo migracao();
}
