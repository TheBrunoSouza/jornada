<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

//print_r($json); // ExtJS JSON post request

if($json->idGrupo){
	if(!$json->idModulo){
		$sqlIns = "INSERT INTO modulo_grupo
				   (modulo, grupo, edit, add, del)
				   VALUES
				   (".$_REQUEST['idModulo'].", ".$json->idGrupo.", ".var_export($json->checkEdit, true).", ".var_export($json->checkAdd, true).", ".var_export($json->checkDel, true)." )";
		//echo $sqlIns;
		$conexao->execSql($sqlIns);
	}
	else{
		if((!$json->checkEdit) and (!$json->checkAdd) and (!$json->checkDel)){
			$sqlDel = "DELETE FROM modulo_grupo WHERE grupo = ".$json->idGrupo." AND modulo = ".$json->idModulo."";
			//echo $sqlDel;
			$conexao->execSql($sqlDel);
		}
		else{
			$sqlUp = "UPDATE modulo_grupo SET
					  edit = ".var_export($json->checkEdit, true).",
					  add  = ".var_export($json->checkAdd, true).",
					  del  = ".var_export($json->checkDel, true)." 
					  WHERE grupo = ".$json->idGrupo."
					  AND modulo = ".$_REQUEST['idModulo']."";
			//echo $sqlUp;
			$conexao->execSql($sqlUp);
		}
	}
}
 
$sqlAux.= "";
if(!$_SESSION['sessionCentral'] and $_REQUEST['grupoUsu']==1)
	$sqlAux.= "";
elseif($_REQUEST['grupoUsu']==6)
	$sqlAux.= " WHERE id_grupo_usuario IN (2, 4, 5, 6)";
elseif($_REQUEST['grupoUsu']==2)
	$sqlAux.= " WHERE id_grupo_usuario IN (2, 4, 5)";

$sqlGrp = "SELECT id_grupo_usuario, descricao, modulo, 
				  CASE WHEN add = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS add,
				  CASE WHEN edit = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS edit,
 				  CASE WHEN del = true
				  	   THEN 'true'
					   ELSE 'false'
				  END AS del
			FROM grupo_usuarios
			LEFT JOIN modulo_grupo ON grupo = id_grupo_usuario AND modulo = '".$_REQUEST['idModulo']."'
			".$sqlAux."
			ORDER BY descricao";
			
//echo "<pre>".$sqlGrp."</pre>";
$resUsu = $conexao->getResult($sqlGrp);

$arrayUsu = array();

foreach($resUsu as $rowUsu){
	$arrayUsu[] = array("idGrupo"=>$rowUsu['id_grupo_usuario'],
						"descGrupo"=>htmlentities($rowUsu['descricao']),
						"idModulo"=>$rowUsu['modulo'],
						"checkAdd"=>filter_var($rowUsu['add'], FILTER_VALIDATE_BOOLEAN),
						"checkEdit"=>filter_var($rowUsu['edit'], FILTER_VALIDATE_BOOLEAN),
						"checkDel"=>filter_var($rowUsu['del'], FILTER_VALIDATE_BOOLEAN));
}
//print_r($arrayUsu);
$array['modulos_grupos'] = $arrayUsu;
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
