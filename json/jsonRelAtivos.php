<?
session_start();
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

$sqlCond = "SELECT condutor.id_condutor, condutor.nome, Max(to_char(evento.data_hora, 'DD/MM/YYYY HH24:MI')) AS dt_hr
			FROM evento, condutor
			WHERE condutor = id_condutor
			AND data_hora::date >= date '".$_REQUEST['dtIni']."'
			AND data_hora::date < date '".$_REQUEST['dtFim']."'
			AND condutor.empresa = ".$_REQUEST['idEmpresa']."
			GROUP BY condutor.id_condutor, condutor.nome
			ORDER BY condutor.nome";
/*
SELECT condutor.id_condutor, condutor.nome
			FROM diario_de_bordo, condutor
			WHERE condutor = id_condutor
			AND data_ini::date >= date '".$_REQUEST['dtIni']."'
			AND data_ini::date < date '".$_REQUEST['dtFim']."'
			AND empresa = ".$_REQUEST['idEmpresa']."
			GROUP BY condutor.id_condutor, condutor.nome
			ORDER BY condutor.nome";
*/
//echo $sqlCond;
$resCond = $conexao->getResult($sqlCond);

$arrayCond = array();

foreach($resCond as $rowCond){
	$arrayCond[] = array("idCondutor"=>$rowCond['id_condutor'],
						 "nmCondutor"=>utf8_encode($rowCond['nome']),
						 "dataHora"=>$rowCond['dt_hr']);
}
//print_r($arrayPlaca);
$array['condutores'] = $arrayCond;
$json = $array;

//$callback = $_REQUEST['jsonp'];

//start output
/*if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {*/
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}
?>
