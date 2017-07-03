<?
error_reporting(0);
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

$sqlEst = "SELECT id_estado, sigla, nome
		   FROM estado
		   ORDER BY nome";
//echo "<pre>".$sqlUsu."</pre>";
$resEst = $conexao->getResult($sqlEst);

$arrayEst = array();

foreach($resEst as $rowEst){
	$arrayEst[] = array("idEstado"=>$rowEst['id_estado'],
						"nmEstado"=>utf8_encode($rowEst['nome']),
						"sglEstado"=>utf8_encode($rowEst['sigla']));
}
//print_r($arrayUsu);
$array['estados'] = $arrayEst;
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
