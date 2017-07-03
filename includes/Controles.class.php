<?php 
/**
 * Classe para controle de permiss�es de acesso nos sistemas Cielo
 * e gera��o de mensagens de resposta.
 * @package Eagle3class
 */
error_reporting(0);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

class Controles{
	public $RemoteAddr;
	private $con = null;
	private $lastError = null;
	
	/**
     * Construtor.
     * @param string $from IP de origem (Normalmente utiliza-se $_SERVER['REMOTE_ADDR'])
     */
	function __construct($arrServer, $conexao=null){
		session_start();
		$this->RemoteAddr=(is_array($arrServer))?$arrServer['HTTP_X_FORWARDED_FOR']:$arrServer;
		$this->RemoteAddr=(empty($this->RemoteAddr))?$arrServer['REMOTE_ADDR']:$this->RemoteAddr;
		if($conexao!=null){
			$this->con = $conexao;
		}
	}
	
	function __destruct(){
		$this->RemoteAddr=null;
	}
	
	/**
     * Fun��o que define uma nova mensagem de erro.
     * @param string $msg Mensagem de erro.
     * @return bool Retorna TRUE.
     */
	public function setError($msg){
		$this->lastError = $msg;
		return true;
	}
	
	/**
     * Fun��o que busca o �ltimo erro gerado.
     * @return string Retorna a mensagem de erro.
     */
	public function getError(){
		return $this->lastError;
	}
	
	/**
     * Fun��o que verifica a validade da sess�o gravada para Usu�rio do Sistema.
     * @param string|array $session Array da sess�o. (Normalmente usa-se $_SESSION)
     * @return bool Retorna TRUE se ok, ou FALSE caso a sess�o expire.
     */
	public function checkUsuario($session){
		if(empty($session['sessionUserId'])|| empty($session['sessionUserLogin'])){
		   $this->msgExpirada("Sua Sessão Expirou!");
		   return false;
		}
		else 
			return true;
	}

	/**
     * Fun��o que retorna o ID do usu�rio gravado na sessao.
     * @return integer Retorna o id do usu�rio.
     */
	public function getUserID($session){
		return $session['sessionUserId'];
	}
	
	/**
     * Fun��o que retorna o login do usu�rio gravado na sessao.
     * @return integer Retorna o login do usu�rio.
     */
	public function getUserLogin($session){
		return $session['sessionUserLogin'];
	}

	/**
     * Fun��o que retorna o id do grupo do usu�rio gravado na sessao.
     * @return integer Retorna o id do grupo do usu�rio.
     */
	public function getUserGrupo($session){
		return $session['sessionUserGroup'];
	}

	/**
     * Fun��o que retorna o id do grupo do usu�rio gravado na sessao.
     * @return integer Retorna o id do grupo do usu�rio.
     */
	public function getUserEmpresa($session){
		return $session['sessionEmpresa'];
	}

			
	/**
     * Fun��o que exibe uma p�gina HTML completa de acesso negado. 
     * @param string $motivo Texto com o motivo do acesso negado.
     */
	public function msgAcessoNegado($motivo, $arqNome){
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
			<html>
			<head><title>Acesso Negado</title>
			<body>
				<p>Acesso negado!</p> <br>".$motivo."<br> M&oacute;dulo: ".$arqNome."
			</body>
			</html>";
	}
	
	/**
     * Fun��o que exibe uma uma mensagem para retorno � tela de login.
     * Utilizada quando a sess�o expirou.
     * @param string $motivo Texto descritivo.
     */
	private function msgExpirada($msg){
		$aux = explode("/",$_SERVER['SCRIPT_URL']);
		$url = str_replace($_SERVER['SCRIPT_URL'],"",$_SERVER['SCRIPT_URI'])."/".$aux[1]."/index.php";
		echo "<script>document.location.href='".$url."?errorMsg=".$msg."';</script>";
	}
	
	private function msgLogon($msg){
		$aux = explode("/",$_SERVER['SCRIPT_URL']);
		$url = str_replace($_SERVER['SCRIPT_URL'],"",$_SERVER['SCRIPT_URI'])."/".$aux[1]."/index.php";
		echo "
		<script>
		if(!alert('".$msg."'))
			document.location.href='".$url."';
		</script>";
	}
	
	/**
	 * Verifica se um grupo tem permiss�o em um modulo.
	 * @param int $grupo
	 * @param int $modulo
	 * @param bool $onlyGrupos Se for true deve desconsiderar a tabela permissao_usuario.
	 * @param int $usuario Se for informado vai buscar as permiss�es da tabela permissao_usuario com o que foi informado, caso contr�rio com o usu�rio da sess�o.
	 * @return boolean true se $grupo tiver permiss�o para $modulo e false se n�o tiver.
	 */
	public function checkPermissao($moduloId = null, $moduloUrl = null){
		$usuario = (!empty($usuario))?$usuario:$this->getUserID($_SESSION);
		$grupo   = (!empty($grupo))?$grupo:$this->getUserGrupo($_SESSION);

		if($moduloId)
			$sqlTmp = "modulo = ".$moduloId."";
		else{
			$aux = explode('/', $moduloUrl);
			$i = count($aux)-1;
			$vlrUrl = $aux[$i];
			$sqlTmp = "modulo IN (SELECT id_modulo FROM modulos WHERE url = '".$vlrUrl."')";
		}
                
		$sqlPermGrp = " 
                    SELECT adicionar, editar, excluir
                    FROM modulo_grupo
                    WHERE grupo = ".$grupo."
                        AND ".$sqlTmp;
//		echo $sqlPermGrp;
                
		$resPermGrp = OCIParse($this->con, $sqlPermGrp);
		OCIExecute($resPermGrp);
		OCIFetchInto($resPermGrp, $ddPermGrp, OCI_ASSOC);

		$checkGrp = (!empty($ddPermGrp))?true:false;
                
		$permissao = Array(
                    "permissao" => $checkGrp,
                    "add"       => $ddPermGrp['ADICIONAR'],
                    "edit"      => $ddPermGrp['EDITAR'],
                    "delete"    => $ddPermGrp['EXCLUIR']
                );

//		$sqlPermissao="SELECT modulo, usuario,
//							  CASE WHEN add = true
//								   THEN 'T'
//								   ELSE 'F'
//							  END AS add,
//							  CASE WHEN edit = true
//								   THEN 'T'
//								   ELSE 'F'
//							  END AS edit,
//							  CASE WHEN del = true
//								   THEN 'T'
//								   ELSE 'F'
//							  END AS del 
//					   FROM modulo_usuario
//					   WHERE usuario=".$usuario."
//					   AND ".$sqlTmp."";
		//echo $sqlPermissao;
//		$res = OCIParse($conexao, $sqlPermissao);
//		OCIExecute($res);
//		OCIFetchInto($res, $dd, OCI_ASSOC);

//		if($res){
//                    $check = true;
//                    $permissao = Array("permissao"=>$check, "add"=>$dd['ADICIONAR'], "edit"=>$dd['EDITAR'], "delete"=>$dd['DELETAR']);
//		}
//		else
//			$check = false;
//		
//		//print_r($permissao);
//		if($check==false and $checkGrp==false and $moduloId==null){
//			$this->msgAcessoNegado(htmlentities('Você não tem permissão para acessar esse módulo!'), $vlrUrl);
//			exit;
//		}
//		else
                return $permissao;
	}
	
	public function getExtra100(){
		if($this->getUserEmpresa($_SESSION)){
			$sqlExtra = "SELECT count(id_configuracao) qtd
						 FROM configuracao conf, configuracao_situacao cs
						 WHERE conf.empresa  = '".$this->getUserEmpresa($_SESSION)."'
						 AND conf.ID_CONFIGURACAO = cs.CONFIGURACAO
						 AND (situacao = 10 OR next_sit = 10) ";
//			echo $sqlExtra;
			$resExtra = OCIParse($this->con, $sqlExtra);
			OCIExecute($resExtra);
			OCIFetchInto($resExtra, $ddExtra, OCI_ASSOC);
			$aux = ($ddExtra['QTD']>0)?'true':'false';
//			echo '--->'.$aux;
			return $aux;
		}
		else
			return 'false';
	}
}
?>
