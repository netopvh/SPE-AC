<?php

namespace App\Classes;

use App\Utils\ConectionTurmalina;

class Turmalina {
        
    function getOrgaos($where = '', $limit) 
    {
        $offset = $limit + 9;
        $sql = "SELECT * FROM ( SELECT COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO FROM VW_LOTACAO WHERE $where NOT (COD_ORGAO IN (1,61,70,91)) GROUP BY COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO ORDER BY COD_ORGAO ) SUN WHERE ROWNUM <= 10";
        $stid = oci_parse( ConectionTurmalina::getInstance(), $sql);
        oci_execute($stid);
        $rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);

        $sql = "SELECT COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO FROM VW_LOTACAO WHERE $where NOT (COD_ORGAO IN (1,61,70,91)) GROUP BY COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO ORDER BY NOME_ORGAO";
        $stid = oci_parse( ConectionTurmalina::getInstance(), $sql);
        oci_execute($stid);
        $rowCount2 = oci_fetch_all($stid, $data2, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        
        return ['data' => $data, 'total' => $rowCount2, 'rowCount' => $rowCount, ];
    }
}