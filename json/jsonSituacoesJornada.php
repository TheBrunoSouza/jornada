<?
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

$sqlSit = "SELECT id_diario, id_situacao, situacao.descricao as situacao, status.descricao as status, to_char(data_ini, 'hh24:mi') as hr_ini, to_char(data_fim, 'hh24:mi') as hr_fim, condutor,
				   to_char(data_fim-data_ini, 'HH24:MI') as tempo
		   FROM diario_de_bordo, situacao, status
		   WHERE condutor = '".$_REQUEST['idCondutor']."'
		   AND data_ini::date = '".$_REQUEST['dtEdt']."'
		   AND id_situacao = diario_de_bordo.situacao
		   AND status = id_status
		   ORDER BY data_ini";
//echo "<pre>".$sqlSit."</pre>";
$resSit = $conexao->getResult($sqlSit);

$arraySit = array();

foreach($resSit as $rowSit){
	$arraySit[] = array("idDiario"=>$rowSit['id_diario'],
						"situacaoID"=>$rowSit['id_situacao'],
						"sitDescricao"=>htmlentities($rowSit['situacao']),
						"stDescricao"=>htmlentities($rowSit['status']),
						"diarioHrIni"=>$rowSit['hr_ini'],
						"diarioHrFim"=>$rowSit['hr_fim'],
						"idCondutor"=>$rowSit['condutor'],
						"diarioTempo"=>$rowSit['tempo']);
}
//print_r($arrayCli);
$array['situacoes'] = $arraySit;
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
