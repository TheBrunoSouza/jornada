<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

//print_r($json); // ExtJS JSON post request

if($json->idGrupo){
	$sqlDel = "DELETE FROM grupo_usuarios WHERE id_grupo_usuario = '".$json->idGrupo."'";
	//echo $sqlDel;
	$conexao->execSql($sqlDel);
}

$sqlAux.= "";
if(!$_SESSION['sessionCentral'] and $_REQUEST['grupoUsu']==1)
	$sqlAux.= "";
elseif($_REQUEST['grupoUsu']==6)
	$sqlAux.= " AND id_grupo_usuario IN (2, 4, 5, 6)";
elseif($_REQUEST['grupoUsu']==2)
	$sqlAux.= " AND id_grupo_usuario IN (2, 4, 5)";

 
$sqlGrp = "SELECT id_grupo_usuario, descricao
		   FROM grupo_usuarios
		   ".$sqlAux."
		   ORDER BY descricao";
//echo "<pre>".$sqlUsu."</pre>";
$resGrp= $conexao->getResult($sqlGrp);

$arrayUsu = array();

foreach($resGrp as $rowGrp){
	$arrayGrp[] = array("idGrupo"=>$rowGrp['id_grupo_usuario'],
						"descGrupo"=>htmlentities($rowGrp['descricao']));
}
//print_r($arrayUsu);
$array['grupos'] = $arrayGrp;
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
