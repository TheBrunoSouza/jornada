<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

//print_r($json); // ExtJS JSON post request

if($json->idUsuario){
	//$sqlDel = "DELETE FROM modulo_usuario WHERE usuario = '".$json->idUsuario."'";
	//echo $sqlDel;
	if(!$json->idModulo){
		$sqlIns = "INSERT INTO modulo_usuario
				   (modulo, usuario, edit, add, del)
				   VALUES
				   (".$_REQUEST['idModulo'].", ".$json->idUsuario.", ".var_export($json->checkEdit, true).", ".var_export($json->checkAdd, true).", ".var_export($json->checkDel, true)." )";
		//echo $sqlIns;
		$conexao->execSql($sqlIns);
	}
	else{
		if((!$json->checkEdit) and (!$json->checkAdd) and (!$json->checkDel)){
			$sqlDel = "DELETE FROM modulo_usuario WHERE usuario = ".$json->idUsuario." AND modulo = ".$json->idModulo."";
			$conexao->execSql($sqlDel);
		}
		else{
			$sqlUp = "UPDATE modulo_usuario SET
					  edit = ".var_export($json->checkEdit, true).",
					  add  = ".var_export($json->checkAdd, true).",
					  del  = ".var_export($json->checkDel, true)." 
					  WHERE usuario = ".$json->idUsuario."
					  AND modulo = ".$_REQUEST['idModulo']."";
			//echo $sqlUp;
			$conexao->execSql($sqlUp);
		}
	}
}
 
$sqlUsu = "SELECT id_usuario, nome, login, modulo, 
				  CASE WHEN modulo_usuario.add = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS add,
				  CASE WHEN modulo_usuario.edit = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS edit,
 				  CASE WHEN modulo_usuario.del = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS del
			FROM usuarios
			LEFT JOIN modulo_usuario ON usuario = id_usuario AND modulo = '".$_REQUEST['idModulo']."'
			WHERE central = ".$_SESSION['sessionCentral']."
			ORDER BY nome";
			
//echo "<pre>".$sqlUsu."</pre>";
$resUsu = $conexao->getResult($sqlUsu);

$arrayUsu = array();

foreach($resUsu as $rowUsu){
	$arrayUsu[] = array("idUsuario"=>$rowUsu['id_usuario'],
						"nmUsuario"=>htmlentities($rowUsu['nome']),
						"loginUsuario"=>$rowUsu['login'],
						"idModulo"=>$rowUsu['modulo'],
						"checkAdd"=>filter_var($rowUsu['add'], FILTER_VALIDATE_BOOLEAN),
						"checkEdit"=>filter_var($rowUsu['edit'], FILTER_VALIDATE_BOOLEAN),
						"checkDel"=>filter_var($rowUsu['del'], FILTER_VALIDATE_BOOLEAN));
}
//print_r($arrayUsu);
$array['modulos_usuarios'] = $arrayUsu;
$json = $array;

$callback = $_REQUEST['jsonp'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
}
?>
