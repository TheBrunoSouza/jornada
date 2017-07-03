<?
//error_reporting(0);
session_start();

require_once('../includes/OracleCieloJornada.class.php');

$OraCielo       = new OracleCielo();
$conexaoOra     = $OraCielo->getCon();
$json_request   = file_get_contents('php://input');
$json           = json_decode($json_request);
$sqlAux         = "";
$arrayGrupo     = array();

//if($json->idGpUsuario){
//    $sqlAux = " AND id_grupo_usuario = ".$json->idGpUsuario."";
//}

if($_REQUEST['grupoUsu'] == 1){
    $sqlAux = "";
}elseif($_REQUEST['grupoUsu'] == 6){
    $sqlAux = " WHERE id_grupo IN (2, 4, 5, 6)";
}elseif($_REQUEST['grupoUsu'] == 2){
    $sqlAux = " WHERE id_grupo IN (2, 4, 5)";
}
 
$sqlGrupo = "
    SELECT id_grupo, descricao
    FROM grupo
    ".$sqlAux."
    ORDER BY descricao";

$respostaGrupo = oci_parse($conexaoOra, $sqlGrupo);
oci_execute($respostaGrupo);
//if(!oci_execute($respostaGrupo)){
//    echo ' Status SELECT: ERRO ->'.$sqlGrupo;exit();
//}else{
//    echo ' Status SELECT: OK ->'.$sqlGrupo;exit();
//}

while (($row = oci_fetch_assoc($respostaGrupo)) != false) {
//    echo  $row['ID_GRUPO'].'<br>';
    $arrayGrupo[] = array(
        "idGpUsuario"   => $row['ID_GRUPO'],
        "descGpUsuario" => $row['DESCRICAO']
    );
}
//print_r($arrayGrupo);

$array['gpusuarios']    = $arrayGrupo;
$json                   = $array;
//$callback               = $_REQUEST['jsonp'];

//start output
/*if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {*/
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}