<?
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

print_r($json); // ExtJS JSON post request

if($json->idModulo){
	$sqlDel = "DELETE FROM modulos WHERE id_modulo = '".$json->idModulo."'";
	//echo $sqlDel;
	$conexao->execSql($sqlDel);
}
 
$sqlMod = "SELECT id_modulo, descricao, icon, url
		   FROM modulos
		   ORDER BY descricao";
//echo "<pre>".$sqlUsu."</pre>";
$resMod = $conexao->getResult($sqlMod);

$arrayMod = array();

foreach($resMod as $rowMod){
	$arrayMod[] = array("idModulo"=>$rowMod['id_modulo'],
						"descModulo"=>htmlentities($rowMod['descricao']),
						"iconModulo"=>$rowMod['icon'],
						"urlModulo"=>$rowMod['url']);
}
//print_r($arrayUsu);
$array['modulos'] = $arrayMod;
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
