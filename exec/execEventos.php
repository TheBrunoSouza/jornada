<?
session_start();

require('../includes/BancoPost.class.php');
$conexao = new BancoPost();
require_once('../includes/Controles.class.php');
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$usuario = $CtrlAcesso->getUserLogin($_SESSION);

$idEmpresa = ($_POST['idEmpresa'])?$_POST['idEmpresa']:$CtrlAcesso->getUserEmpresa($_SESSION);


if($_REQUEST['acao']=='delete'){
	/*foreach ($json as $values){
		$values = (is_array($values))?$values:Array(0=>$values);
		foreach($values as $result){
			$sqlDel = "DELETE FROM evento 
					   WHERE id_evento = ".$result->{'idEvento'}."";
			//echo $sqlDel."<br>";
			//$arrayMsg[] = array("DELETE"=>$sqlDel);
			$res = $conexao->execSql($sqlDel);
			echo "{\"success\":false,\"msg\":\"Erro ao excluir o evento\"}";
		}
	}*/
}
elseif($_REQUEST['acao']=='insert'){
	$codigoEventoTipo = $_POST['codigoEventoTipo'];		
	$sqlDescEventoTipo = "(select descricao 
						  from evento_tipo 
						  where codigo = '".$codigoEventoTipo."')";
			
			$sqlNew = "INSERT INTO evento
					   (
					   data_hora, 
					   data_db, 
					   latitude, 
					   longitude, 
					   codigo, 
					   condutor, 
					   descricao, 
					   placa, 
					   data_integrado, 
					   parametro,
					   tecnologia,
					   processado,
					   empresa,
					   usuario)
					   VALUES
					   ( 
					    to_timestamp('".$_POST['dtDataHoraEvento']."', 'DD/MM/YYYY HH24:MI:SS'), 
					    to_timestamp('".$_POST['dtDataHoraEvento']."', 'DD/MM/YYYY HH24:MI:SS'),					    
						0,--latitude
						0,--longitude
						".$codigoEventoTipo.",												
						".$_POST['idCondutorh'].",						
						".$sqlDescEventoTipo.",		
						'".$_POST['placaCondutor']."',									
						null,--data_integrado
						null,--parametro
						1,--tecnologia
						'F',--processado
						".$idEmpresa.",
						'".$usuario."')";
						
			
			$res = $conexao->getResult($sqlNew);

			 
			if (is_array($res)){
				echo "{\"success\":true,\"msg\":\"Evento inserido com sucesso!\"}";
			}else{				
				if (strpos($conexao->getErro(),'duplicate key') > 0){
					$msg = 'Evento já existente';
				}else{				
					$msg = '';
				}
				
				echo "{\"success\":false,\"msg\":\"Erro ao incluir o evento. ".$msg."\"}";
			}

}

?>
