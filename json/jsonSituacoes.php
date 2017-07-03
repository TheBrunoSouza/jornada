<?
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo       = new OracleCielo();
$conexaoOra     = $OraCielo->getCon();
$CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu     = $CtrlAcesso->getUserEmpresa($_SESSION);

$json_request   = file_get_contents('php://input');
$json           = json_decode($json_request);

//print_r($json); // ExtJS JSON post request

if($json['idSituacao']){
    $sqlDel             = "DELETE FROM situacao WHERE id_situacao = '".$json['idSituacao']."'";
    $respostaDel        = OCIParse($conexaoOra, $sqlDel);
    $respostaExecucao   = OCIExecute($respostaDel);
    oci_free_statement($respostaDel);
}

//if($_REQUEST['tpSituacao']=='jornada'){
//	$sqlAux = "WHERE accordion = 'F'";
//}elseif($_REQUEST['tpSituacao']=='situacao'){
	$sqlAux = "WHERE accordion = 'T' and id_situacao NOT IN (1,2,3,11,12)";
//}
 
$sqlGrp = "
        SELECT  id_situacao, 
                CASE WHEN id_situacao = 6 THEN
                    'Refei&ccedil&atildeo'
                ELSE
                    descricao 
                END AS descricao
        FROM situacao
        ".$sqlAux."
        ORDER BY id_situacao";
//echo "<pre>".$sqlGrp."</pre>";

$respostaCP         = OCIParse($conexaoOra, $sqlGrp);
$respostaExecucao   = OCIExecute($respostaCP);
$arrayUsu           = array();

while(OCIFetchInto($respostaCP, $rowGrp, OCI_ASSOC)){
    $arrayGrp[] = array(
        "idSituacao"    => $rowGrp['ID_SITUACAO'],
        "descSituacao"  => $rowGrp['DESCRICAO']
    );
}

oci_free_statement($respostaCP);

$array['situacoes'] = $arrayGrp;
$json               = $array;
//print_r($json);exit();

function remover_caracter($string) {
    $string = preg_replace("/[áàâãä]/", "a", $string);
    $string = preg_replace("/[ÁÀÂÃÄ]/", "A", $string);
    $string = preg_replace("/[éèê]/", "e", $string);
    $string = preg_replace("/[ÉÈÊ]/", "E", $string);
    $string = preg_replace("/[íì]/", "i", $string);
    $string = preg_replace("/[ÍÌ]/", "I", $string);
    $string = preg_replace("/[óòôõö]/", "o", $string);
    $string = preg_replace("/[ÓÒÔÕÖ]/", "O", $string);
    $string = preg_replace("/[úùü]/", "u", $string);
    $string = preg_replace("/[ÚÙÜ]/", "U", $string);
    $string = preg_replace("/ç/", "c", $string);
    $string = preg_replace("/Ç/", "C", $string);
    $string = preg_replace("/[][><}{)(:;!'*%~^`&#@]/", "", $string);
    return $string;
}

oci_close($conexaoOra);

/*
}
$callback = $_REQUEST['jsonp'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {*/
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}