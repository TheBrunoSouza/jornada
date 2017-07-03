<?
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

$exp        = explode('-', $_REQUEST['dtEdt']);
$dataOracle =  $exp[2].$exp[1].$exp[0];

$sqlSit = "
    SELECT  
            id_jornada, 
            id_situacao, 
            situacao.descricao as situacao, 
            to_char(data_ini, 'hh24:mi') as hr_ini, to_char(data_fim, 'hh24:mi') as hr_fim, condutor,
            getintervaltochar(data_ini, data_fim) as tempo
    FROM    jornada, situacao
    WHERE   condutor = '".$_REQUEST['idCondutor']."'
            AND trunc(jornada.data_ini) = to_date('".$dataOracle."', 'DDMMYYYY') 
            AND id_situacao = jornada.situacao
    ORDER BY data_ini";
//echo "<pre>".$sqlSit."</pre>";

$respostaCP         = OCIParse($conexaoOra, $sqlSit);
$respostaExecucao   = OCIExecute($respostaCP);

while(OCIFetchInto($respostaCP, $rowSit, OCI_ASSOC)){
	$arraySit[] = array(
            "idJornada"     => $rowSit['ID_JORNADA'],
            "situacaoID"    => $rowSit['ID_SITUACAO'],
            "sitDescricao"  => htmlentities($rowSit['SITUACAO']),
            "diarioHrIni"   => $rowSit['HR_INI'],
            "diarioHrFim"   => $rowSit['HR_FIM'],
            "idCondutor"    => $rowSit['CONDUTOR'],
            "diarioTempo"   => $rowSit['TEMPO']
        );
}

oci_free_statement($respostaCP);
oci_close($conexaoOra);

$array['situacoes'] = $arraySit;
$json               = $array;
//print_r($json);exit();

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