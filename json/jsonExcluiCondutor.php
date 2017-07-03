<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();


   $info = $_POST['condutores'];
 
    $data = json_decode(stripslashes($info));
    
    $id = $data->idCondutor;

	if($id){
		$sqlDel = "DELETE FROM condutor WHERE id_condutor = '".$id."'";

		$res = $conexao->getResult($sqlDel);
		
		if (is_array($res)){
			echo "{\"success\":true,\"msg\":\"\"}";
		}else{
			if (strpos($conexao->getErro(),'foreign key') > 0){
					$msg = 'Não é possível excluir o condutor. O mesmo possui registro de atividades no sistema.';
				}else{				
					$msg = 'Erro ao excluir o condutor';
				}
			echo "{\"success\":false,\"msg\":\"".$msg."\"}";	        
		} 
		
	}
?>
