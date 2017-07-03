<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();


   $info = $_POST['alertas'];
 
    $data = json_decode(stripslashes($info));
    
    $id = $data->idEvento;

	if($id){
		$sqlDel = "DELETE FROM evento WHERE id_evento = '".$id."'";

		$res = $conexao->getResult($sqlDel);
		
		if (is_array($res)){
			echo "{\"success\":true,\"msg\":\"\"}";
		}else{
			$msg= 'Erro ao excluir o evento.';
			echo "{\"success\":false,\"msg\":\"".$msg."\"}";	        
		} 
		
	}
?>
