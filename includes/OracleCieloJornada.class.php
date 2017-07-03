<?php
//ini_set('display_errors', 0);
class OracleCielo{
	private $RemoteAddr;
	private $monitDB="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=enterprise-scan.cielo.ind.br)(PORT=1521))(CONNECT_DATA=(SERVER=POOLED)(SERVICE_NAME=monit)))";
	private $monitUser="JORNADA";
	private $monitPass="psqljor";
	public $activeParse=null;
	public $activeParseLimit=null;
	public $activeParsePages=null;

	public $conexao = null;
	
	function __construct(){
		$this->conexao=oci_connect($this->monitUser,$this->monitPass,$this->monitDB);
	}

	function __destruct(){
		oci_close($this->conexao);
	}
	
	/**
     * Fun��o que retorna uma conex�o ativa com a base MONITORAMENTO (Oficial).
     * @return mixed Conex�o Oracle.
     */
	public function getCon(){
//		echo "GET CON ----<br>";
		return $this->conexao;
	}
	
	/**
     * Fun��o que executa um SQL e d� commit (Sem Retorno de Dados, utilizado para UPDATE, INSERT, DELETE).
     * @return bool|string True se a execu��o foi completada com sucesso, ou a mensagem de erro em caso de erro.
     */
	public function execSql($sql, $commit=true){
		$sqlParse = oci_parse($this->conexao, $sql);
		if(@oci_execute($sqlParse,OCI_NO_AUTO_COMMIT)){
			if($commit===true){
				oci_commit($this->conexao);
			}
			return true;
		}
		else{
			$err=OCIError($sqlParse);
			return $err['message']; 
		}
	}
	
	public function execUpdate($sql, $commit=true){
		$sqlParse = oci_parse($this->conexao, $sql);
		if(@oci_execute($sqlParse,OCI_NO_AUTO_COMMIT)){
			if($commit===true){
				oci_commit($this->conexao);	
			}
			return oci_num_rows($sqlParse);
		}
		else{
			return false; 
		}
	}
	
	
	public function execSelect($sql){
		if($this->conexao!=null){
			$res = oci_parse($this->conexao,$sql);
			if(@oci_execute($res,OCI_NO_AUTO_COMMIT)){
				$dados = oci_fetch_assoc($res);
				oci_free_statement($res);
				return $dados;
			}
			else 
				return null;
		}
		else 
			return null;
	}
	
	public function commit(){
		oci_commit($this->conexao);	
	}
	
	public function rollback(){
		oci_rollback($this->conexao);
	}
}
?>
