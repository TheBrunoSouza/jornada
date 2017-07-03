<?
//require('../includes/BancoPost.class.php');
//$conexao = new BancoPost();

require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();

//$data_request = json_decode(file_get_contents('php://i1nput'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);
//print_r($json); // ExtJS JSON post request
$sqlGrp = "select id_tipo_evento, descricao, codigo 
			from evento_tipo 
			where codigo in (802,803,806,807,808,809) 
			order by codigo";
//echo "<pre>".$sqlUsu."</pre>";
$resGrp= $conexao->getResult($sqlGrp);

$arrayUsu = array();

foreach($resGrp as $rowGrp){
	$arrayGrp[] = array("idEventoTipo"=>$rowGrp['id_tipo_evento'],
						"descEventoTipo"=>utf8_encode($rowGrp['descricao']),
						"codigoEventoTipo"=>$rowGrp['codigo']);
}
//print_r($arrayUsu);
$array['eventosTipo'] = $arrayGrp;
$json = $array;
/*
$callback = $_REQUEST['jsonp'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {*/
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}
?>
