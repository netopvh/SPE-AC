<?php

	class ConectionTURMALINA {
		private static $instance;

		public static function getInstance() {
			if (!isset(self::$instance)) {
				try {
					self::$instance = oci_connect( DATABASE_TURMALINA['username'], DATABASE_TURMALINA['password'], '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ' . DATABASE_TURMALINA['host'] . ' )(PORT = ' . DATABASE_TURMALINA['port'] . ' ))(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = ' . DATABASE_TURMALINA['service'] . ' )))', DATABASE_TURMALINA['mode'] );
				} catch (\Throwable $th) {
					echo $th->getMessage();
				}
			}
			return self::$instance;
		}
	}
	
	try {
		$sql = "SELECT COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO FROM VW_LOTACAO WHERE NOT (COD_ORGAO IN (1,61,70,91)) GROUP BY COD_ORGAO, NOME_ORGAO, SIGLA_ORGAO";
		$stid = oci_parse( ConectionTURMALINA::getInstance(), $sql);
		oci_execute($stid);
		$rowCount = oci_fetch_all($stid, $data, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		
		// return ['data' => $data, 'rowCount' => $rowCount ];
		
		var_dump($data);
	} catch(Exception $e){
		var_dump($e);
	}
	finally{
		echo "Não conseguiu conectar ao banco de dados\n";
	}
	