<?
error_reporting(0);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
session_start();

require_once('../includes/OracleCieloJornada.class.php');
$OraCielo=new OracleCielo();
$conexao=$OraCielo->getCon();

//$data_request = json_decode(file_get_contents('php://input'));
$json_request = file_get_contents('php://input');
$json = json_decode($json_request);

//print_r($json); // ExtJS JSON post request

if($json->idEmpresa){
	$sqlDel = "DELETE FROM empresa WHERE id_empresa = '".$json->idEmpresa."'";
	//echo $sqlDel;
	$resDel = OCIParse($conexao, $sqlDel);
	OCIExecut($resDel);
}
$sqlAux="";

$sqlCli = "SELECT id_empresa, empresa.nome
			FROM monitoramento.empresa
			WHERE empresa.id_empresa NOT IN (SELECT empresa 	
											 FROM empresa_jornada)
			AND empresa.ativa = 'T'
			ORDER BY empresa.nome";
//echo "<pre>".$sqlCli."</pre>";
//exit;
$resCli = OCIParse($conexao, $sqlCli);
OCIExecute($resCli);
$arrayCli = array();
while(OCIFetchInto($resCli, $ddCli, OCI_ASSOC)){
	$arrayCli[] = array("idEmpresa"=>$ddCli['ID_EMPRESA'],
						"nmEmpresa"=>utf8_encode($ddCli['NOME']));
}
//print_r($arrayCli);
$array['empresas'] = $arrayCli;
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
