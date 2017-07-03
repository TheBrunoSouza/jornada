<?
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

 
$sqlGrp = "SELECT  atualizandodados
		   FROM empresa
		   where id_empresa = ".$_REQUEST['idEmpresaAtualizDados'];

$resGrp= $conexao->getResult($sqlGrp);

foreach($resGrp as $rowGrp){
	$arrayGrp[] = array("atualizDados"=>$rowGrp['atualizandodados']);
}

$array['atualizacao'] = $arrayGrp;
$json = $array;

//$callback = $_REQUEST['jsonp'];

//start output
//if ($callback) {
//    header('Content-Type: text/javascript');
//    echo $callback . '(' . json_encode($json) . ');';
//} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}
?>
