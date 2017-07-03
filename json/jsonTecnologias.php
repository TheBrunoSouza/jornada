<?
error_reporting(0);
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

$sqlEst = "SELECT id_tecnologia, descricao
		   FROM tecnologia
		   ORDER BY descricao";
//echo "<pre>".$sqlUsu."</pre>";
$resEst = $conexao->getResult($sqlEst);

$arrayEst = array();

foreach($resEst as $rowEst){
	$arrayEst[] = array("idTecnologia"=>$rowEst['id_tecnologia'],
						"nmTecnologia"=>utf8_encode($rowEst['descricao']));
}
//print_r($arrayUsu);
$array['tecnologias'] = $arrayEst;
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
